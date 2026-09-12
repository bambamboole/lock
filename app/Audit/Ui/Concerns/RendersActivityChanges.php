<?php
declare(strict_types=1);

namespace App\Audit\Ui\Concerns;

use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Size;

trait RendersActivityChanges
{
    /**
     * @param  array<string, mixed>  $properties
     * @return array<int, Text>
     */
    private function changeLines(array $properties): array
    {
        $lines = [];

        foreach ($properties as $key => $value) {
            $lines[] = Text::make(str((string) $key)->headline().': '.$this->formatValue($value))->size(Size::Sm);
        }

        return $lines;
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
