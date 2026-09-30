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

```php
<?php

namespace App\Service;

use Connected\Conexteo\Client\ConexteoClientRegistry;
use Connected\Conexteo\Model\MessageRcs;
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
     * @return list<MessageRcs>
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
  `getRepliesAsMessages()` la même chose hydratée en `MessageRcs`. Les deux
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
> `$conexteoClients->get('conexteo')->sendRcs($phone, (new ConexteoRCSOptions())->content(new TextContent($text)))`.

RCS enrichi : cartes, carrousels, boutons et options
----------------------------------------------------

Un `SmsMessage` sans options part en RCS texte. Pour le reste, passez un
`Connected\Conexteo\Rcs\ConexteoRCSOptions` à `SmsMessage::options()` :

```php
use Connected\Conexteo\Rcs\Choice\CallChoice;
use Connected\Conexteo\Rcs\Choice\ReplyChoice;
use Connected\Conexteo\Rcs\Choice\UrlChoice;
use Connected\Conexteo\Rcs\ConexteoRCSOptions;
use Connected\Conexteo\Rcs\Content\CardContent;

$sms = (new SmsMessage($phone, 'Votre colis #4821 est expédié : https://exemple.fr/suivi'))
    ->transport('conexteo')
    ->options((new ConexteoRCSOptions())
        ->content(new CardContent(
            title: 'Commande #4821 expédiée',
            description: 'Livraison demain entre 9h et 13h',
            mediaUrl: 'https://cdn.exemple.fr/colis.png',
            choices: [
                new UrlChoice('Suivre le colis', 'https://exemple.fr/suivi'),
                new CallChoice('Nous appeler', '+33100000000'),
                new ReplyChoice('Reporter'),
            ],
        ))
        ->externalId('order-4821-shipped'));

$this->texter->send($sms);
```

Contenus (`Connected\Conexteo\Rcs\Content`) :

| Classe | Payload |
|---|---|
| `TextContent($text)` | `type: text` |
| `CardContent($title, $description, $mediaUrl, $choices)` | `type: card`, 4 boutons maximum. Média public en JPEG, PNG, GIF ou MP4 (pas de WebP) |
| `CarouselContent(...$cards)` | `type: carousel`, de 2 à 10 `CardContent`, facturé comme un seul message |

Boutons (`Connected\Conexteo\Rcs\Choice`), libellé de 25 caractères maximum :

| Classe | Action |
|---|---|
| `ReplyChoice($text)` | réponse rapide, revient dans `getReplies()` |
| `UrlChoice($title, $url)` | ouvre un lien |
| `CallChoice($title, $phoneNumber)` | lance un appel |
| `LocationChoice($title, $label, $latitude, $longitude)` | affiche un lieu |

Options facultatives de `ConexteoRCSOptions` :

| Méthode | Effet |
|---|---|
| `fallback(new SmsFallback($content, $sender))` | SMS envoyé aux destinataires qui ne reçoivent pas le RCS |
| `checkCapability()` | seuls les destinataires compatibles reçoivent le RCS, les autres reçoivent directement le SMS de repli |
| `externalId($id)` | clé de dédoublonnage |
| `scheduleAt($date)` | envoi différé (converti en UTC) |
| `contactVariables(['prenom' => 'Alice'])` | variables de personnalisation du destinataire |
| `campaignName($name)` | nom de la campagne dans l'interface Conexteo |

- Quand le contenu est fourni dans les options, le sujet du `SmsMessage` sert de
  SMS de repli, sauf si `fallback()` est renseigné.
- `new SmsMessage($phone, $subject, $from)` : `$from` remplace le `sender` du DSN
  pour ce message.
- Les limites de l'API (libellés, nombre de boutons et de cartes) sont vérifiées
  à la construction et lèvent une `\InvalidArgumentException` avant tout appel HTTP.

Montée de version
-----------------

Voir [UPGRADE.md](UPGRADE.md).
