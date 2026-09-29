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

DSN example
-----------

```
CONEXTEO_DSN=conexteo://APP_ID:API_KEY@default?sender=SENDER
```

where:
- `APP_ID` is your Conexteo App ID
- `API_KEY` is your Conexteo API Key
- `SENDER` is your sender (optional)

Envoi via Notifier
------------------

```yaml
# config/packages/notifier.yaml
framework:
    notifier:
        texter_transports:
            conexteo: '%env(CONEXTEO_DSN)%'
```

```php
$texter->send((new SmsMessage('+33600000000', 'Bonjour'))->transport('conexteo'));
```

Lecture des réponses
--------------------

Notifier n'expose pas ses transports : la lecture des réponses passe par
`Connected\Conexteo\Client\ConexteoClientInterface`, à déclarer comme service
à partir du même DSN (un service par compte Conexteo) :

```yaml
# config/services.yaml
services:
    app.conexteo_client:
        class: Connected\Conexteo\Client\ConexteoClient
        factory: ['@Connected\Conexteo\Client\ConexteoClientFactory', 'createFromDsnString']
        arguments: ['%env(CONEXTEO_DSN)%']

    Connected\Conexteo\Client\ConexteoClientInterface: '@app.conexteo_client'
```

```php
public function __construct(private readonly ConexteoClientInterface $conexteoClient)
{
}

// Charge utile brute renvoyée par l'API : ['items' => [...], 'meta' => [...]]
$raw = $this->conexteoClient->getReplies($messageId, page: 1, limit: 50);

// Même chose, hydratée en Connected\Conexteo\Model\MessageRcs
['messages' => $messages, 'meta' => $meta] = $this->conexteoClient->getRepliesAsMessages($messageId);
```

`$messageId` est l'identifiant renvoyé à l'envoi par `SentMessage::getMessageId()`.

> [!WARNING]
> `TexterInterface::send()` renvoie `null` quand Notifier est routé vers Messenger
> en asynchrone : aucun `message_id` n'est alors récupérable. Pour lire les
> réponses, envoyez en synchrone ou utilisez `ConexteoClientInterface::sendRcs()`.

En cas d'erreur de l'API, le client lève une
`Connected\Conexteo\Exception\ConexteoApiException` (le transport la convertit en
`TransportException` pour Notifier). Dans les tests, remplacez
`ConexteoClientInterface` par un mock.

Montée de version
-----------------

Voir [UPGRADE.md](UPGRADE.md).
