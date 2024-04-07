# Comments Plugin docs

## Strategies
There are different main strategies:

- **Action**: Posting to the same action as the comments are displayed, e.g. a specific entity view
- **Controller**: Posting to the plugin Comments controller with a redirect back to the referer (current view)

Each of those can also be done using AJAX instead of normal PRG.

### Action

Preferred way, directly posting to the same action.
It uses the beforeRender() callback as that one has already the main entity loaded in the view vars.



### Controller

This can be needed, if you cannot post to the same action due to a conflict, e.g.
PRG redirect pattern interfering, or another `request->is('post')` check in that action already.

Make sure to set ACL for this controller if only logged in people are allowed to comment.

The `Comments.Comments` helper has a `url()` method you can use to get here the URL to post to.

## Admin Backend
Go to `/admin/comments`.

Make sure you set up ACL to only have admins access this part.
