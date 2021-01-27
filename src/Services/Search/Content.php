<?php namespace Semknox\Core\Services\Search;

class Content {
    
    /**
     * @var Content[]
     */
    protected $contentResult;

    protected $contentSection;

    /**
     * Initialize a content results object
     *
     * @param array $contentResults
     */
    public function __construct(array $contentSection, array $contentResult)
    {
        $this->contentResult = $contentResult;
        $this->contentSection = $contentSection;
    }

    /**
     * Return the section name
     * @return string
     */
    public function getSectionName()
    {
        return $this->contentSection['name'];
    }

    /**
     * Return the section total Results
     * @return string
     */
    public function getSectionTotalResults()
    {
        return $this->contentSection['totalResults'];
    }

    /**
     * Return the item name
     * @return string
     */
    public function getName()
    {
        return $this->contentResult['name'];
    }

    /**
     * Get the link to the
     */
    public function getLink()
    {
        return $this->contentResult['link'];
    }

    /**
     * Return the main image for this item.
     * @return string
     */
    public function getImage()
    {
        return $this->contentResult['image'];
    }

    /**
     * Return the relevance of this item.
     * @return string
     */
    public function getRelevance()
    {
        return $this->contentResult['relevance'];
    }


}