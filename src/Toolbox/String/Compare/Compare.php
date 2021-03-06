<?php

namespace Simtabi\Pheg\Toolbox\String\Compare;

class Compare
{

    public function __construct()
    {
    }

    public function all($first, $second)
    {
        $start       = microtime(true);

        $similar     = $this->similarText($first, $second);
        $smg         = $this->smg($first, $second);
        $jaroWinkler = $this->jaroWinkler($first, $second);
        $levenshtein = $this->levenshtein($first, $second);

        $end = microtime(true) - $start;

        return [
            'data' => [
                'first_string'        => $first,
                'second_string'       => $second,
                'run_time_in_seconds' => $end,
            ],
            'similar_text' => $similar,
            'smg'          => $smg,
            'jaroWinkler'  => $jaroWinkler,
            'levenshtein'  => $levenshtein
        ];
    }

    /**
     * Run a basic levenshtein comparison using PHP's built-in function
     *
     * @param string $first First string to compare
     * @param string $second Second string to compare
     *
     * @return string Returns the phrase passed in
     */
    public function levenshtein($first, $second)
    {
        $l = new \Simtabi\Pheg\Toolbox\String\Compare\Levenshtein();

        return $l->compare($first, $second);
    }

    public function jaroWinkler($first, $second)
    {
        $jw = new \Simtabi\Pheg\Toolbox\String\Compare\JaroWinkler();

        return $jw->compare($first, $second);
    }

    public function smg($first, $second)
    {
        $o = new \Simtabi\Pheg\Toolbox\String\Compare\SmithWatermanGotoh();
        return $o->compare($first, $second);
    }

    public function similarText($first, $second)
    {
        similar_text($first, $second, $percent);

        return $percent;
    }
}
