<?php

declare(strict_types=1);

namespace App\Support\Nps;

/**
 * Accumulator for categorical NPS metrics (excluding invalid / NSA buckets from score denominators).
 */
final class NpsBuckets
{
    public int $promoters = 0;

    public int $detractors = 0;

    public int $neutrals = 0;

    public int $excluded = 0;

    /**
     * Scale 1–5 with optional NSA sentinel (stored as literal string "nsa").
     */
    public function tallyScale15(mixed $raw): void
    {
        if ($raw === null) {
            $this->excluded++;

            return;
        }

        if ($raw === 'nsa') {
            $this->excluded++;

            return;
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT);
        if ($value === false) {
            $this->excluded++;

            return;
        }

        if ($value >= 4) {
            $this->promoters++;
        } elseif ($value <= 3) {
            $this->detractors++;
        } else {
            $this->excluded++;
        }
    }

    /**
     * Classical 0–10 NPS buckets (RN03).
     */
    public function tallyScale010(mixed $raw): void
    {
        if ($raw === null) {
            $this->excluded++;

            return;
        }

        $value = filter_var($raw, FILTER_VALIDATE_INT);
        if ($value === false || $value < 0 || $value > 10) {
            $this->excluded++;

            return;
        }

        if ($value >= 9) {
            $this->promoters++;
        } elseif ($value <= 6) {
            $this->detractors++;
        } else {
            $this->neutrals++;
        }
    }

    public function denominator15(): int
    {
        return $this->promoters + $this->detractors;
    }

    public function denominator010(): int
    {
        return $this->promoters + $this->detractors + $this->neutrals;
    }

    public function nps15(): ?float
    {
        $denominator = $this->denominator15();
        if ($denominator === 0) {
            return null;
        }

        return round((($this->promoters - $this->detractors) / $denominator) * 100, 1);
    }

    public function nps010(): ?float
    {
        $denominator = $this->denominator010();
        if ($denominator === 0) {
            return null;
        }

        return round((($this->promoters - $this->detractors) / $denominator) * 100, 1);
    }
}
