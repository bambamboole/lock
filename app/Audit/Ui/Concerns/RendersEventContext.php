<?php
declare(strict_types=1);

namespace App\Audit\Ui\Concerns;

use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Size;

trait RendersEventContext
{
    /**
     * The provenance of a row first, then whatever the event carried. A value
     * nothing recorded is left out rather than rendered empty.
     *
     * @param  array<string, string|null>  $labelled
     * @param  array<string, mixed>  $context
     * @return array<int, Text>
     */
    private function detailLines(array $labelled, array $context): array
    {
        $lines = [];

        foreach ($labelled as $label => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = $this->line($label.': '.$value);
            }
        }

        foreach ($context as $key => $value) {
            $lines[] = $this->line(str((string) $key)->headline().': '.$this->formatValue($value));
        }

        return $lines;
    }

    private function line(string $text): Text
    {
        return Text::make($text)->size(Size::Sm);
    }

    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            if (array_keys($value) === ['from', 'to']) {
                return $this->formatValue($value['from']).' → '.$this->formatValue($value['to']);
            }

            return $value === [] ? '—' : implode(', ', array_map($this->formatValue(...), $value));
        }

        return is_scalar($value) ? (string) $value : (string) json_encode($value);
    }
}
