<?php

declare(strict_types=1);

namespace YaPro\ApiRation\Response\JsonLd;

use ArrayObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class CollectionJsonLdResponse extends JsonResponse
{
	public const ITEMS = 'items';
	public const TOTAL_ITEMS = 'totalItems';

    public function __construct(array $items = [], int $totalItems = 0, int $status = Response::HTTP_OK)
	{
		$input = new ArrayObject([
                "@type" => "hydra:Collection",
                "hydra:member" => $items,
                "hydra:totalItems" => $totalItems,
                /* Example:
                "hydra:view": {
                    "@id": "/books?page=1",
                    "@type": "hydra:PartialCollectionView",
                    "hydra:first": "/books?page=1",
                    "hydra:last": "/books?page=4",
                    "hydra:next": "/books?page=2"
                },
                "hydra:search": {
                    "@type": "hydra:IriTemplate",
                    "hydra:template": "/books{?properties[],order[id],order[title],order[publicationDate],title,author}",
                    "hydra:variableRepresentation": "BasicRepresentation",
                    "hydra:mapping": [
                      {
                        "@type": "IriTemplateMapping",
                        "variable": "properties[]",
                        "property": null,
                        "required": false
                      },
                      {
                        "@type": "IriTemplateMapping",
                        "variable": "order[id]",
                        "property": "id",
                        "required": false
                      },
                      {
                        "@type": "IriTemplateMapping",
                        "variable": "order[title]",
                        "property": "title",
                        "required": false
                      },
                      {
                        "@type": "IriTemplateMapping",
                        "variable": "order[publicationDate]",
                        "property": "publicationDate",
                        "required": false
                      },
                      {
                        "@type": "IriTemplateMapping",
                        "variable": "title",
                        "property": "title",
                        "required": false
                      },
                      {
                        "@type": "IriTemplateMapping",
                        "variable": "author",
                        "property": "author",
                        "required": false
                      }
                    ]
                }
                */
            ]);
		parent::__construct($input, $status);
	}
}
