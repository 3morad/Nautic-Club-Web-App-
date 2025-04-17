<?php

namespace App\Util;

class PhpStanParserConfig
{
    /** @var bool */
    private $useLinesAttributes;
    
    /** @var bool */
    private $useIndexAttributes;

    public function __construct(bool $useLinesAttributes = false, bool $useIndexAttributes = false)
    {
        $this->useLinesAttributes = $useLinesAttributes;
        $this->useIndexAttributes = $useIndexAttributes;
    }

    public function useLinesAttributes(): bool
    {
        return $this->useLinesAttributes;
    }

    public function useIndexAttributes(): bool
    {
        return $this->useIndexAttributes;
    }
} 