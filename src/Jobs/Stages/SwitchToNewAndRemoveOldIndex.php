<?php

namespace SoeurngSar\LaravelScoutOpenSearch\Jobs\Stages;

use OpenSearch\Client;
use SoeurngSar\LaravelScoutOpenSearch\OpenSearch\Index;
use SoeurngSar\LaravelScoutOpenSearch\OpenSearch\Params\Indices\Alias\Get;
use SoeurngSar\LaravelScoutOpenSearch\OpenSearch\Params\Indices\Alias\Update;
use SoeurngSar\LaravelScoutOpenSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class SwitchToNewAndRemoveOldIndex
{
    /**
     * @var ImportSource
     */
    private $source;
    /**
     * @var Index
     */
    private $index;

    /**
     * @param  ImportSource  $source
     * @param  Index  $index
     */
    public function __construct(ImportSource $source, Index $index)
    {
        $this->source = $source;
        $this->index = $index;
    }

    public function handle(Client $elasticsearch): void
    {
        $source = $this->source;
        $params = Get::anyIndex($source->searchableAs());
        $response = $elasticsearch->indices()->getAlias($params->toArray());

        $params = new Update();
        foreach ($response as $indexName => $alias) {
            if ($indexName != $this->index->name()) {
                $params->removeIndex((string) $indexName);
            } else {
                $params->add((string) $indexName, $source->searchableAs());
            }
        }
        $elasticsearch->indices()->updateAliases($params->toArray());
    }

    public function estimate(): int
    {
        return 1;
    }

    public function title(): string
    {
        return 'Switching to the new index';
    }
}
