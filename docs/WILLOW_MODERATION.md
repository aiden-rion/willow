# Report Moderation

- `adm/willow_report.php` supports block and restore independently of report workflow status.
- `willow/moderation.lib.php` stores one state per board/topic post in `g5_willow_moderation` (configured prefix applies).
- Original posts, attachments, payments and points are not deleted or altered.
- The acting admin and timestamp are stored; action notes are appended to reports for the same target.
- Public feed/search queries exclude blocked targets before LIMIT. Direct post views and Willow like/comment endpoints reject blocked targets, including the author's requests.
- Before deploying to another database, bootstrap `common.php` in CLI, include `willow/moderation.lib.php`, and call `willow_moderation_install()`. Create the table before serving the changed public queries. Opening report management also ensures the table exists.
- Run `php scripts/test_moderation.php` from the site root. Tests use a connection-local TEMPORARY shadow table and read existing posts; no production post or moderation state is modified.
- Previously downloaded content or publicly accessible attachment URLs are not revoked by this feature.
