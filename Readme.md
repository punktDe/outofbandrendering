# Punktde.OutOfBandRendering

[![Latest Stable Version](https://poser.pugx.org/punktde/outofbandrendering/v/stable)](https://packagist.org/packages/punktde/outofbandrendering) [![Total Downloads](https://poser.pugx.org/punktde/outofbandrendering/downloads)](https://packagist.org/packages/punktde/outofbandrendering)

Supplies an EelHelper to do out of band rendering of fusion objects.

This can be useful for example to render complex input for indexing into an Elasticsearch index. 

### Installation

```
$ composer require punktde/outofbandrendering
```

### Example

Settings.yaml

    Neos:
      ContentRepository:
        Search:
          defaultContext:
            FusionRendering: PunktDe\OutOfBandRendering\Eel\FusionRenderingHelper


Render the quick-link during index time using the given prototypePath:

    __myProperty:
      search:
        elasticSearchMapping:
          type: string
          index: not_analyzed
          include_in_all: false
        indexing: '${FusionRendering.render(node, "pathToProtoType")}'
