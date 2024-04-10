# Using the authentication extension

Dot-twigrenderer extends Twig with functions that use functionality from [laminas/laminas-authentication](https://github.com/laminas/laminas-authentication) to check if the user is authenticated and to get the authenticated identity object respectively.

```php
public function hasIdentity(): bool;

public function getIdentity(): ?IdentityInterface;
```

## Example usage

```twig
{% if hasIdentity() %}

    Welcome, {% getIdentity().username %}!

{% endif %}
```
