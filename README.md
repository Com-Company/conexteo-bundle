Conexteo Notifier
=================

Provides [Conexteo](https://developers.conexteo.com/) integration for Symfony Notifier.

Les messages sont envoyés en **RCS** (`POST /messages/rcs`). Le transport expose
également la lecture des réponses reçues (`GET /messages/replies`), qui ne rentre
pas dans le contrat du composant Notifier.

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

Lecture des réponses
--------------------

`getReplies()` / `getRepliesAsMessages()` sont portées par le transport lui-même.
Le composant Notifier n'exposant pas les transports individuellement, le bundle
enregistre le sien dès qu'un DSN est configuré :

```yaml
# config/packages/conexteo.yaml
conexteo:
    dsn: '%env(CONEXTEO_DSN)%'
```

`Connected\Conexteo\Transport\ConexteoTransport` devient alors autowirable :

```php
public function __construct(private readonly ConexteoTransport $transport)
{
}

// Charge utile brute renvoyée par l'API : ['items' => [...], 'meta' => [...]]
$raw = $this->transport->getReplies($messageId, page: 1, limit: 50);

// Même chose, hydratée en Connected\Conexteo\Model\MessageRcs
['messages' => $messages, 'meta' => $meta] = $this->transport->getRepliesAsMessages($messageId);
```

Sans clé `dsn`, le bundle se limite au transport Notifier et n'enregistre rien.

Montée de version
-----------------

Voir [UPGRADE.md](UPGRADE.md).
