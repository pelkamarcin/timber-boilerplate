# Components

Ten katalog przechowuje Twigowe komponenty wielokrotnego użytku. Struktura:

```
components/
  navigation/
    menu.twig
    posts-pagination.twig
  media/
    thumbnail.twig
  post/
    tease.twig
  comments/
    comment.twig
    comment-form.twig
```

Zasady:

- każdy komponent jest niezależny i przyjmujew dane przez `with`;
- nazwy katalogów opisują przeznaczenie (navigation/media/post/comments);
- nowe komponenty dodawaj jako `components/<sekcja>/<nazwa>.twig` i dokumentuj wymagane dane na górze pliku w
  komentarzu.
