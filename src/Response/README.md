https://demo.api-platform.com/docs :
```json
{
  "@context": "/contexts/Book",
  "@id": "/books",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/books/0bc4b331-31b0-4478-8057-4238df12a54a",
      "@type": "http://schema.org/Book",
      "isbn": "9780736900799",
      "title": "Nulla consectetur beatae autem dolorem maiores.",
      "description": "In pariatur omnis blanditiis consectetur itaque. Cupiditate praesentium itaque et reiciendis. Sapiente est adipisci quaerat voluptas vitae quia molestiae.",
      "author": "Frederic Dooley",
      "publicationDate": "2006-12-31T00:00:00+00:00",
      "reviews": [
        {
          "@id": "/reviews/2d684629-527a-4deb-8817-5ff4cd75ae6f",
          "@type": "http://schema.org/Review",
          "body": "Deleniti at excepturi temporibus excepturi. Sapiente quia reprehenderit eligendi repudiandae molestiae deserunt. Non exercitationem et quia nulla eos ex facere pariatur. Laborum aut id consequuntur tenetur."
        },
        {
          "@id": "/reviews/8c0309d0-8369-4d24-82ae-119a0a239101",
          "@type": "http://schema.org/Review",
          "body": "Necessitatibus repudiandae consequatur qui rerum. Qui nulla et consequatur natus similique earum. Et et sit tempora hic recusandae velit. Magnam ducimus eius sunt recusandae quidem."
        }
      ]
    },
  ],
  "hydra:totalItems": 99,
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
}
```
