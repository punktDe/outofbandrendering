# Punktde.OutOfBandRendering

[![Latest Stable Version](https://poser.pugx.org/punktde/outofbandrendering/v/stable)](https://packagist.org/packages/punktde/outofbandrendering) [![Total Downloads](https://poser.pugx.org/punktde/outofbandrendering/downloads)](https://packagist.org/packages/punktde/outofbandrendering)

* Suplies a factory for building a ControllerContext out of a CLI request, that puts everything in place for rendering fusion code, includign node and resource links. 
* Supplies an EelHelper to do out of band rendering of fusion objects.

This can be useful for example to render complex input for indexing into an Elasticsearch index. 

### Installation

```bash
$ composer require punktde/outofbandrendering
```

### Configuration

You'll need to set the base URI.

```yaml
Neos:
  Flow:
    http:
      baseUri: https://example.com/
```

### Example

In our Settings.yaml

```yaml
Neos:
  ContentRepository:
    Search:
      defaultContext:
        FusionRendering: PunktDe\OutOfBandRendering\Eel\FusionRenderingHelper
```
Render a suggestion during index time using the given prototypePath:

```yaml
__myProperty:
  search:
    elasticSearchMapping:
      type: keyword
    indexing: '${FusionRendering.render(node, "pathToProtoType")}'
```
