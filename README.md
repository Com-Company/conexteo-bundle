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
> réponses, envoyez en synchrone ou appelez directement
> `$conexteoClients->get('conexteo')->sendRcs($phone, $text)`.

Montée de version
-----------------

Voir [UPGRADE.md](UPGRADE.md).
