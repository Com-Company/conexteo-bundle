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

### 2. Transport Notifier (envoi)

```yaml
# config/packages/notifier.yaml
framework:
    notifier:
        texter_transports:
            conexteo: '%env(CONEXTEO_DSN)%'
```

### 3. Client Conexteo (lecture des réponses) : DSN à réexposer

Notifier construit ses transports en interne et ne les expose pas comme services.
Pour lire les réponses, il faut donc **redéclarer le même DSN** dans un service
client, dans l'application :

```yaml
# config/services.yaml
services:
    app.conexteo_client:
        class: Connected\Conexteo\Client\ConexteoClient
        factory: ['@Connected\Conexteo\Client\ConexteoClientFactory', 'createFromDsnString']
        arguments: ['%env(CONEXTEO_DSN)%']

    Connected\Conexteo\Client\ConexteoClientInterface: '@app.conexteo_client'
```

Avec plusieurs comptes Conexteo (plusieurs DSN), déclarez un service client par
compte et injectez-les par alias nommé, par exemple
`Connected\Conexteo\Client\ConexteoClientInterface $conexteoLocationClient: '@app.conexteo_client.location'`.

Cette étape est inutile si l'application ne fait qu'envoyer des messages.

Utilisation
-----------

### Envoi

```php
$sentMessage = $texter->send((new SmsMessage('+33600000000', 'Bonjour'))->transport('conexteo'));
$messageId = $sentMessage?->getMessageId(); // à conserver pour lire les réponses
```

> [!WARNING]
> `TexterInterface::send()` renvoie `null` quand Notifier est routé vers Messenger
> en asynchrone : aucun `message_id` n'est alors récupérable. Pour lire les
> réponses, envoyez en synchrone ou utilisez `ConexteoClientInterface::sendRcs()`.

### Lecture des réponses

```php
public function __construct(private readonly ConexteoClientInterface $conexteoClient)
{
}

// Charge utile brute renvoyée par l'API : ['items' => [...], 'meta' => [...]]
$raw = $this->conexteoClient->getReplies($messageId, page: 1, limit: 50);

// Même chose, hydratée en Connected\Conexteo\Model\MessageRcs
['messages' => $messages, 'meta' => $meta] = $this->conexteoClient->getRepliesAsMessages($messageId);
```

### Erreurs et tests

En cas d'erreur de l'API, le client lève une
`Connected\Conexteo\Exception\ConexteoApiException` (le transport la convertit en
`TransportException` pour Notifier). Dans les tests, remplacez
`ConexteoClientInterface` par un mock.

Montée de version
-----------------

Voir [UPGRADE.md](UPGRADE.md).
