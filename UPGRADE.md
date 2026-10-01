UPGRADE FROM 5.x to 6.0
=======================

> [!IMPORTANT]
> **Le contrat Conexteo doit être paramétré pour l'envoi de RCS avant la montée
> de version.** Sans cette option sur le compte associé à l'`APP_ID`, l'API
> rejette tous les envois et le transport lève une `TransportException`.
> Pour continuer à envoyer des SMS classiques, restez en `^5.1`.

Envoi
-----

- Les messages partent désormais sur `POST /messages/rcs` au lieu de `POST /messages`.
- Le payload envoyé change :

  Avant :
  ```json
  {"message_type": 1, "content": "...", "recipients": ["+33600000000"], "sender": "..."}
  ```

  Après :
  ```json
  {"type": "text", "data": {"text": "..."}, "contacts": [{"recipient": "+33600000000"}], "sender": "..."}
  ```

- Les cartes, carrousels, boutons et options facultatives (repli SMS,
  `checkCapability`, `shorturl`, `external_id`, `scheduleAt`...) passent par
  `SmsMessage::options(new ConexteoRCSOptions(...))`, un DTO
  dans `Connected\Conexteo\DTO\Rcs`. Voir le
  [README](README.md#rcs-enrichi--cartes-carrousels-boutons-et-options).
- `SmsMessage::getFrom()` remplace le `sender` du DSN quand il est renseigné.

Gestion des erreurs
-------------------

- Toute réponse HTTP >= 400 lève une `TransportException`. Auparavant, tout code
  différent de 200 était considéré comme une erreur : un `201` ou un `202` renvoyé
  par l'API ne fait donc plus échouer l'envoi.
- Une réponse non JSON sur un code de succès lève une `TransportException`.
- `SentMessage::getMessageId()` n'est renseigné que si l'API retourne un
  `message_id`, au lieu de provoquer une erreur quand la clé est absente.

Architecture
------------

- Les appels HTTP sont sortis du transport vers
  `Connected\Conexteo\Client\ConexteoClient` (interface `ConexteoClientInterface`).
  `ConexteoTransport` ne fait plus qu'adapter Notifier au client : son constructeur
  prend désormais un `ConexteoClientInterface` au lieu de `$appId` / `$apiKey`.
  Rien ne change si le transport est créé par Notifier via le DSN.
- Nouveau : lecture des réponses avec `ConexteoClientInterface::getReplies()` /
  `getRepliesAsMessages()`. Le client s'obtient avec
  `ConexteoClientRegistry::get('nom_du_transport')`, à partir des DSN déjà
  déclarés dans `framework.notifier.texter_transports` : aucune configuration
  supplémentaire. Voir le [README](README.md#utilisation).
- `ConexteoClientInterface::sendRcs()` prend un `ConexteoRCSOptions` et un
  expéditeur facultatif : `sendRcs(string $recipient, ConexteoRCSOptions $options, ?string $sender = null)`.
- Nouveau : `ConexteoClientInterface::estimateRcsCredits()` renvoie les crédits
  nécessaires sans envoyer (`resumeBefore=true`).
- `symfony/yaml` fait désormais partie des dépendances (le bundle charge sa
  configuration en YAML).
