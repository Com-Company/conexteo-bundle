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

Gestion des erreurs
-------------------

- Toute réponse HTTP >= 400 lève une `TransportException`. Auparavant, tout code
  différent de 200 était considéré comme une erreur : un `201` ou un `202` renvoyé
  par l'API ne fait donc plus échouer l'envoi.
- Une réponse non JSON sur un code de succès lève une `TransportException`.
- `SentMessage::getMessageId()` n'est renseigné que si l'API retourne un
  `message_id`, au lieu de provoquer une erreur quand la clé est absente.

Configuration
-------------

- Nouvelle clé optionnelle `conexteo.dsn`. Renseignée, elle enregistre le service
  `Connected\Conexteo\Transport\ConexteoTransport`, nécessaire pour lire les
  réponses (`getReplies()` / `getRepliesAsMessages()`). Voir le [README](README.md#lecture-des-réponses).
  Sans cette clé, rien ne change pour l'envoi via Notifier.
