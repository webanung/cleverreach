<?php

namespace CR\OfficialCleverreach\Utility;

use CleverReach\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\ComplexCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleCollectionSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItem;
use CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItems;
use CR\OfficialCleverreach\Exceptions\ContentTypeNotFoundException;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class SchemaProvider
{
    /**
     * Gets all supported searchable items.
     *
     * @return \CleverReach\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItems
     *   Object containing all searchable items supported by module.
     */
    public function getSearchableItems()
    {
        $searchableItems = new SearchableItems();
        $contentTypes = $this->getContentTypes();

        foreach ($contentTypes as $code => $name) {
            $searchableItems->addSearchableItem(new SearchableItem($code, $name));
        }

        return $searchableItems;
    }

    /**
     * @param string $contentType
     *
     * @return SearchableItemSchema
     *
     * @throws ContentTypeNotFoundException
     */
    public function getSchema($contentType)
    {
        if (!array_key_exists($contentType, $this->getContentTypes())) {
            throw new ContentTypeNotFoundException("Content type '$contentType' is not found.");
        }

        $schema = $this->getBaseSchema();
        switch ($contentType) {
            case 'page':
                $schema = array_merge($schema, $this->getPageAndBlogPostsAttributes(), $this->getPageAttributes());
                break;
            case 'blog_post':
                $schema = array_merge($schema, $this->getPageAndBlogPostsAttributes(), $this->getBlogPostAttributes());
                break;
            case 'news':
                $schema = array_merge($schema, $this->getNewsAttributes());
                break;
        }

        return new SearchableItemSchema($contentType, $schema);
    }

    /**
     * @return array
     */
    public function getContentTypes()
    {
        $types = ['page' => 'Standard Page'];

        if (ExtensionManagementUtility::isLoaded('blog')) {
            $types['blog_post'] = 'Blog post';
        }

        if (ExtensionManagementUtility::isLoaded('news')) {
            $types['news'] = 'News';
        }

        return $types;
    }

    /**
     * @return array
     */
    private function getBaseSchema()
    {
        return [
            new SimpleSchemaAttribute('id', 'Id', true, [Conditions::EQUALS], SchemaAttributeTypes::NUMBER),
            new SimpleSchemaAttribute(
                'date',
                'Creation Date',
                true,
                [
                    Conditions::EQUALS,
                    Conditions::GREATER_EQUAL,
                    Conditions::GREATER_THAN,
                    Conditions::LESS_EQUAL,
                    Conditions::LESS_THAN,
                ],
                SchemaAttributeTypes::DATE
            ),

            new SimpleSchemaAttribute('main_image', 'Main image', false, [], SchemaAttributeTypes::IMAGE),
            new SimpleSchemaAttribute('url', 'Page link', false, [], SchemaAttributeTypes::URL),

            new SimpleCollectionSchemaAttribute(
                'tags',
                'Tags',
                true,
                [Conditions::CONTAINS],
                SchemaAttributeTypes::TEXT
            ),
            new SimpleCollectionSchemaAttribute(
                'visible',
                'Visible',
                true,
                [Conditions::EQUALS],
                SchemaAttributeTypes::BOOL
            ),
            new SimpleCollectionSchemaAttribute(
                'categories',
                'Categories',
                true,
                [Conditions::CONTAINS],
                SchemaAttributeTypes::TEXT
            ),
            new ComplexCollectionSchemaAttribute(
                'images',
                'Images',
                false,
                [],
                [
                    new SimpleSchemaAttribute('images.name', 'Image title', false, [], SchemaAttributeTypes::TEXT),
                    new SimpleSchemaAttribute('images.image', 'Image url', false, [], SchemaAttributeTypes::IMAGE),
                ]
            ),

        ];
    }

    /**
     * @return array
     */
    private function getPageAndBlogPostsAttributes()
    {
        return [
            new SimpleSchemaAttribute(
                'title',
                'Title',
                true,
                [Conditions::EQUALS, Conditions::CONTAINS],
                SchemaAttributeTypes::TEXT
            ),
            new SimpleSchemaAttribute(
                'subtitle',
                'Subtitle',
                true,
                [Conditions::EQUALS, Conditions::CONTAINS],
                SchemaAttributeTypes::TEXT
            ),
            new SimpleSchemaAttribute('abstract', 'Abstract', true, [Conditions::CONTAINS], SchemaAttributeTypes::TEXT),

            new SimpleSchemaAttribute(
                'update_date',
                'Last Update',
                true,
                [
                    Conditions::EQUALS,
                    Conditions::GREATER_EQUAL,
                    Conditions::GREATER_THAN,
                    Conditions::LESS_EQUAL,
                    Conditions::LESS_THAN,
                ],
                SchemaAttributeTypes::DATE
            ),
            new SimpleCollectionSchemaAttribute(
                'text_blocks',
                'Page contents (Text)',
                true,
                [Conditions::CONTAINS],
                SchemaAttributeTypes::TEXT
            ),
            new SimpleCollectionSchemaAttribute(
                'html_blocks',
                'Page contents (Html)',
                true,
                [Conditions::CONTAINS],
                SchemaAttributeTypes::HTML
            ),
        ];
    }

    /**
     * @return array
     */
    private function getPageAttributes()
    {
        return [
            new SimpleSchemaAttribute(
                'author',
                'Author',
                true,
                [Conditions::EQUALS, Conditions::CONTAINS],
                SchemaAttributeTypes::AUTHOR
            ),
            new SimpleSchemaAttribute('full_article', 'Page html', false, [], SchemaAttributeTypes::HTML),
        ];
    }

    /**
     * @return array
     */
    private function getBlogPostAttributes()
    {
        return [
            new SimpleCollectionSchemaAttribute(
                'authors',
                'Authors',
                true,
                [Conditions::CONTAINS],
                SchemaAttributeTypes::AUTHOR
            )
        ];
    }

    /**
     * @return array
     */
    private function getNewsAttributes()
    {
        return [
            new SimpleSchemaAttribute(
                'title',
                'Header',
                true,
                [Conditions::EQUALS, Conditions::CONTAINS],
                SchemaAttributeTypes::TEXT
            ),
            new SimpleSchemaAttribute('teaser', 'Teaser', true, [Conditions::CONTAINS], SchemaAttributeTypes::TEXT),
            new SimpleSchemaAttribute('content', 'News content', false, [], SchemaAttributeTypes::HTML),
            new SimpleSchemaAttribute(
                'author',
                'Author',
                true,
                [Conditions::EQUALS, Conditions::CONTAINS],
                SchemaAttributeTypes::AUTHOR
            ),
        ];
    }
}