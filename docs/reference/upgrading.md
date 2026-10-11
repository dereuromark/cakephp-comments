# Upgrading

Check [composer.json](https://github.com/dereuromark/cakephp-comments/blob/master/composer.json) for the supported dependency versions before updating. Keep an application test for comment creation, deletion, and admin access.

## Current development changes

These changes belong to the development branch until a release includes them:

- The component enforces `allowAnonymous`, accepts either `comment` or `content`, and scopes flat lists to the current model and record.
- The component honors `viewVariable` and redirects successful submissions to the current route and passed arguments.
- Public submissions and deletions fall back to the application home page when no local referer is available.
- Request identities are read before legacy auth and session fallbacks. Integer and string representations of the same user ID compare correctly for owner deletion.
- Parent validation includes the model, so records with the same ID in different tables cannot share a reply thread.
- The admin preserves host redirects and responses, supports an Authorization request service, and retains explicit HTTP exceptions from its access callback.
- Admin templates use standard CakePHP helpers. Admin editing can change moderation flags while target fields stay read-only.
- `/admin/comments` resolves directly to the list action. Helpers clear the current prefix when building public add URLs.

If your component previously allowed anonymous submissions while `allowAnonymous` was false, enable it deliberately or require a signed-in user. Review any overridden callback that returns comments without scoping them.

The new documentation separates supported behavior from historical placeholder configuration. Existing private/spam columns are storage flags, not an automatic public visibility or approval policy.
