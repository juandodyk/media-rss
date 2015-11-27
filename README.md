# media-rss

## Produzco mis propios RSS

Algunas páginas ofrecen RSS. No siempre sucede, y cuando sucede, los RSS expuestos pueden tener dos problemas: no incluyen el contenido de los posts, o no permiten filtrar contenido. Hay servicios -pagos- que ofrecen esto. Como soy rata, escribí uno propio.

Corro esto en un hosting con php y mysql gratuito. Los rss se generan al llamar a `retrieve.php`. Quedan guardados estáticamente en una carpeta `./rss`. Los RSS están hechos para funcionar con Feedly. Sé que con otros agregadores no funcionan.

Ejemplos:

* [Anfibia](https://feedly.com/i/subscription/feed/http://pterosaurio.xp3.biz/media/rss/anfibia.rss)
* [Columnistas de Ámbito Financiero](https://feedly.com/i/subscription/feed/http://pterosaurio.xp3.biz/media/rss/ambito.rss)
* [Monkey Cage](https://feedly.com/i/subscription/feed/http://pterosaurio.xp3.biz/media/rss/monkeycage.rss) del [Washington Post](http://www.washingtonpost.com/blogs/monkey-cage/) bien formateado.
* El blog [Free Exchange](https://feedly.com/i/subscription/feed/http://pterosaurio.xp3.biz/media/rss/econfreeexchange.rss) de The Economist.
* Las columnas de [@mecasullo](twitter.com/mecasullo) en [Nueva Ciudad](https://feedly.com/i/subscription/feed/http://pterosaurio.xp3.biz/media/rss/nuevaciudad.rss).

## Miro las tapas de ciertos diarios

Las leo en `tapas.php`.
