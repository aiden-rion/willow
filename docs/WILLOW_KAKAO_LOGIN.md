# Kakao Login

Uses the bundled Gnuboard social plugin, not a separate authentication service.

## Configuration

- Admin: `/adm/config_form.php`, SNS settings.
- `cf_social_login_use`: master switch (leave OFF until developer settings are ready).
- `cf_social_servicelist`: select `kakao` without removing other configured providers.
- `cf_kakao_rest_key`: REST API key.
- `cf_kakao_client_secret`: Client Secret, entered by the administrator when enabled in Kakao.
- `cf_kakao_js_apikey`: JavaScript key; not needed by the REST login flow itself.
- Never commit keys, tokens, or the Admin key.

## Kakao Developers

Enable Kakao Login and register this exact redirect URI:

`https://willlow.org/plugin/social/?hauth.done=kakao`

Configure required consent items. The stock signup form asks for email if the
provider does not return it. This does not certify a phone number or writer status.

## Flow and verification

The four custom login skins include the stock social login skin. Desktop uses
the stock popup and mobile uses a same-window redirect. Guest access is allowed
only for the plugin entry, popup, signup form, and signup submission paths.

Existing linked identities follow Gnuboard login_check; new identities follow
the stock social signup and account-linking flow. No matching by nickname or
automatic merging into phone accounts has been added.

Kakao state is session-bound, single-use, and expires after ten minutes. Both
token exchange and profile requests verify TLS certificates.

Before enabling for everyone, test consent cancellation, new signup, repeat
login, existing-account linking, blocked accounts, popup and mobile redirects.
Provider-side settings and a real consenting Kakao account are required for
end-to-end verification. The Client Secret was not supplied during preparation.
