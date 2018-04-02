# Punktde.OutOfBandRendering

Supplies an EelHelper to do out of band rendering of fusion objects.

This can be useful to render snippets for quick-links in search.

### Example

Settings.yaml

    Neos:
      ContentRepository:
        Search:
          defaultContext:
            FusionRendering: PunktDe\OutOfBandRendering\Eel\FusionRenderingHelper


Render the quick-link during index time using the given prototypePath:

      indexing: '${FusionRendering.render(node, "pathToProtoType")}'
