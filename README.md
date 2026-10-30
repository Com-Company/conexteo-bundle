Conexteo Notifier
=================

Provides [Conexteo](https://developers.conexteo.com/) integration for Symfony Notifier.

Les messages sont envoyés en **RCS** (`POST /messages/rcs`). Le bundle fournit aussi
un client, `ConexteoClient`, pour lire les réponses reçues (`GET /messages/replies`),
qui ne rentre pas dans le contrat du composant Notifier.

> [!IMPORTANT]
> **Le contrat Conexteo doit être paramétré pour l'envoi de RCS.**
> À partir de la version 6.0, tous les messages partent sur l'endpoint RCS.
> Si l'option RCS n'est pas activée sur le compte associé à l'`APP_ID`, l'API
> rejette les envois et le transport lève une `TransportException`.
> Contactez votre interlocuteur Conexteo pour faire activer le RCS **avant**
> de monter en version. Pour continuer à envoyer des SMS classiques, restez en `^5.1`.

Prérequis
---------

- PHP >= 8.3
- Symfony 7.x ou 8.x
- Un contrat Conexteo **paramétré pour l'envoi de RCS**

Configuration
-------------

### 1. DSN

```dotenv
# .env
CONEXTEO_DSN=conexteo://APP_ID:API_KEY@default?sender=SENDER
```

- `APP_ID` : App ID Conexteo
- `API_KEY` : clé d'API Conexteo
- `SENDER` : expéditeur (optionnel)

### 2. Transport Notifier

```yaml
# config/packages/notifier.yaml
framework:
    notifier:
        texter_transports:
            conexteo: '%env(CONEXTEO_DSN)%'
```

C'est la seule configuration : le bundle relit ces DSN pour la lecture des réponses,
il n'y a rien à redéclarer.

Utilisation
-----------

Les classes `Connected\Conexteo\...` ci-dessous sont fournies par le bundle
(à partir de la 6.0) : il n'y a rien à créer dans l'application.

L'envoi passe toujours par `TexterInterface::send()` avec un `SmsMessage` :

- **sans options**, le sujet du `SmsMessage` part en RCS texte (exemple ci-dessous) ;
- **avec `ConexteoRCSOptionsDTO`**, le message part en carte, carrousel, avec boutons,
  repli SMS, envoi différé... Voir [RCS enrichi](#rcs-enrichi--cartes-carrousels-boutons-et-options).

```php
<?php

namespace App\Service;

use Connected\Conexteo\Client\ConexteoClientRegistry;
use Connected\Conexteo\DTO\MessageRcsDTO;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;

final class RcsService
{
    public function __construct(
        private readonly TexterInterface $texter,
        private readonly ConexteoClientRegistry $conexteoClients,
    ) {
    }

    /**
     * Retourne le message_id Conexteo, à conserver pour lire les réponses.
     */
    public function send(string $phone, string $text): ?string
    {
        $sms = (new SmsMessage($phone, $text))->transport('conexteo');

        return $this->texter->send($sms)?->getMessageId();
    }

    /**
     * @return list<MessageRcsDTO>
     */
    public function fetchReplies(string $messageId): array
    {
        ['messages' => $messages] = $this->conexteoClients->get('conexteo')->getRepliesAsMessages($messageId);

        return $messages;
    }
}
```

- `ConexteoClientRegistry::get()` prend le nom du transport déclaré dans
  `texter_transports`, le même que dans `SmsMessage::transport()`. Avec plusieurs
  comptes Conexteo, chaque transport a son client : `get('conexteo_location')`.
- `getReplies()` renvoie la charge utile brute de l'API (`['items' => [...], 'meta' => [...]]`),
  `getRepliesAsMessages()` la même chose hydratée en `MessageRcsDTO`. Les deux
  acceptent `page` et `limit` (50 par défaut).
- Si le transport est déclaré en `null://null` (en dev par exemple), `get()` renvoie
  un client qui n'appelle pas l'API et ne renvoie aucune réponse.
- En cas d'erreur de l'API, le client lève une
  `Connected\Conexteo\Exception\ConexteoApiException` (le transport la convertit
  en `TransportException` pour Notifier).

> [!WARNING]
> `TexterInterface::send()` renvoie `null` quand Notifier est routé vers Messenger
> en asynchrone : aucun `message_id` n'est alors récupérable. Pour lire les
> réponses, envoyez en synchrone ou appelez directement le client :

```php
use Connected\Conexteo\DTO\Rcs\ConexteoRCSOptionsDTO;
use Connected\Conexteo\DTO\Rcs\Content\TextContentDTO;

$options = new ConexteoRCSOptionsDTO(content: new TextContentDTO($text));
$messageId = $this->conexteoClients->get('conexteo')->sendRcs($phone, $options);
```

RCS enrichi : cartes, carrousels, boutons et options
----------------------------------------------------

Un `SmsMessage` sans options part en RCS texte. Pour le reste, passez un
`Connected\Conexteo\DTO\Rcs\ConexteoRCSOptionsDTO` à `SmsMessage::options()`.
C'est un DTO : tout se renseigne par le constructeur (arguments nommés) ou par
ses propriétés publiques.

```php
use Connected\Conexteo\DTO\Rcs\Choice\CallChoiceDTO;
use Connected\Conexteo\DTO\Rcs\Choice\ReplyChoiceDTO;
use Connected\Conexteo\DTO\Rcs\Choice\UrlChoiceDTO;
use Connected\Conexteo\DTO\Rcs\ConexteoRCSOptionsDTO;
use Connected\Conexteo\DTO\Rcs\Content\CardContentDTO;

$options = new ConexteoRCSOptionsDTO(
    content: new CardContentDTO(
        mediaUrl: 'https://cdn.exemple.fr/colis.png',
        title: 'Commande #4821 expédiée',
        description: 'Livraison demain entre 9h et 13h',
        choices: [
            new UrlChoiceDTO('Suivre le colis', 'https://exemple.fr/suivi'),
            new CallChoiceDTO('Nous appeler', '+33100000000'),
            new ReplyChoiceDTO('Reporter'),
        ],
    ),
    externalId: 'order-4821-shipped',
);

// Équivalent, propriété par propriété
$options->campaignName = 'Expéditions';

$sms = (new SmsMessage($phone, 'Votre colis #4821 est expédié : https://exemple.fr/suivi'))
    ->transport('conexteo')
    ->options($options);

$this->texter->send($sms);
```

Contenus (`Connected\Conexteo\DTO\Rcs\Content`) :

| Classe | Payload |
|---|---|
| `TextContentDTO($text)` | `type: text` |
| `CardContentDTO($mediaUrl, $title, $description, $choices)` | `type: card`. Média public obligatoire (jpeg, jpg, png, gif, mp4, h263, m4v, m4p, mpeg, webm), titre, description et boutons facultatifs |
| `CarouselContentDTO($cards, $choices)` | `type: carousel`, une liste de `CardContentDTO` et des boutons communs facultatifs. Facturé comme un seul message |

Boutons (`Connected\Conexteo\DTO\Rcs\Choice`) :

| Classe | Action |
|---|---|
| `ReplyChoiceDTO($text)` | réponse rapide, revient dans `getReplies()` |
| `UrlChoiceDTO($title, $url)` | ouvre un lien |
| `CallChoiceDTO($title, $phoneNumber)` | lance un appel |
| `LocationChoiceDTO($title, $latitude, $longitude, $label)` | affiche un lieu. Coordonnées en nombre, en chaîne numérique ou en variable de contact (`'#perso1#'`) |

Propriétés de `ConexteoRCSOptionsDTO` (toutes facultatives) :

| Propriété | Champ | Effet |
|---|---|---|
| `content` | `type` / `data` | contenu du RCS. Sans contenu, le sujet du `SmsMessage` part en RCS texte |
| `fallback: new SmsFallbackDTO($content, $sender)` | `fallback` | SMS envoyé aux destinataires qui ne reçoivent pas le RCS |
| `checkCapability: true` | `checkCapability` | seuls les contacts déjà analysés compatibles RCS le reçoivent, les autres reçoivent directement le SMS de repli. Nécessite une liste analysée |
| `shortUrl: new ShortUrlDTO($url, ShortUrlMode::UNIQUE)` | `shorturl` | lien raccourci par Conexteo (`UNIQUE` ou `MULTIPLE`) |
| `externalId` | `external_id` | dédoublonnage : un second envoi avec le même identifiant lève une `TransportException` (HTTP 409) |
| `scheduleAt: new \DateTimeImmutable(...)` | `scheduleAt` | envoi différé, converti en UTC (`2025-01-01T10:00:00Z`) |
| `contactVariables: ['prenom' => 'Alice']` | `contacts[0]` | variables de personnalisation du destinataire (valeurs en chaîne) |
| `campaignName` | `name` | nom de la campagne dans l'interface Conexteo |

- Quand le contenu est fourni dans les options, le sujet du `SmsMessage` sert de
  SMS de repli, sauf si `fallback` est renseigné.
- `new SmsMessage($phone, $subject, $from)` : `$from` remplace le `sender` du DSN
  pour ce message.
- Le bundle ne vérifie pas les limites (longueurs, nombre de boutons ou de cartes) :
  c'est l'API qui les applique, et un message hors limites lève une
  `TransportException` avec l'erreur renvoyée par Conexteo. Voir la
  [documentation Conexteo](https://developers.conexteo.com/) pour les valeurs à jour.

### Estimer les crédits sans envoyer

```php
$estimate = $conexteoClients->get('conexteo')->estimateRcsCredits($phone, $options);
// ['sms' => ['credits_required' => ...], 'rcs' => ['credits_required' => ...], 'recipients_count' => ...]
```

Appelle `POST /messages/rcs?resumeBefore=true` : l'API calcule les crédits
nécessaires sans envoyer le message.

Montée de version
-----------------

Voir [UPGRADE.md](UPGRADE.md).
