<?php

namespace Laravel\Boost\Install\Cli;

class DisplayHelper
{
    /**
     * @param array<int, array<int|string, mixed>> $data
     */
    public static function datatable(array $data, int $cols = 80): void
    {
        if (empty($data)) {
            return;
        }

        // Calculate column widths
        $columnWidths = [];
        foreach ($data as $row) {
            $colIndex = 0;
            foreach ($row as $cell) {
                $length = mb_strlen((string) $cell);
                if (! isset($columnWidths[$colIndex]) || $length > $columnWidths[$colIndex]) {
                    $columnWidths[$colIndex] = $length;
                }
                $colIndex++;
            }
        }

        // Add padding
        $columnWidths = array_map(fn ($width) => $width + 2, $columnWidths);

        // Unicode box drawing characters
        $topLeft = '╭';
        $topRight = '╮';
        $bottomLeft = '╰';
        $bottomRight = '╯';
        $horizontal = '─';
        $vertical = '│';
        $cross = '┼';
        $topT = '┬';
        $bottomT = '┴';
        $leftT = '├';
        $rightT = '┤';

        // Draw top border
        $topBorder = $topLeft;
        foreach ($columnWidths as $index => $width) {
            $topBorder .= str_repeat($horizontal, $width);
            if ($index < count($columnWidths) - 1) {
                $topBorder .= $topT;
            }
        }
        $topBorder .= $topRight;
        echo $topBorder.PHP_EOL;

        // Draw rows
        $rowCount = 0;
        foreach ($data as $row) {
            $line = $vertical;
            $colIndex = 0;
            foreach ($row as $cell) {
                $cellStr = ($colIndex === 0) ? "\e[1m".$cell."\e[0m" : $cell;
                $padding = $columnWidths[$colIndex] - mb_strlen($cell);
                $line .= ' '.$cellStr.str_repeat(' ', $padding - 1).$vertical;
                $colIndex++;
            }
            echo $line.PHP_EOL;

            // Draw separator between rows (except after last row)
            if ($rowCount < count($data) - 1) {
                $separator = $leftT;
                foreach ($columnWidths as $index => $width) {
                    $separator .= str_repeat($horizontal, $width);
                    if ($index < count($columnWidths) - 1) {
                        $separator .= $cross;
                    }
                }
                $separator .= $rightT;
                echo $separator.PHP_EOL;
            }
            $rowCount++;
        }

        // Draw bottom border
        $bottomBorder = $bottomLeft;
        foreach ($columnWidths as $index => $width) {
            $bottomBorder .= str_repeat($horizontal, $width);
            if ($index < count($columnWidths) - 1) {
                $bottomBorder .= $bottomT;
            }
        }
        $bottomBorder .= $bottomRight;
        echo $bottomBorder.PHP_EOL;
    }

    /**
     * @param array<int, string> $items
     */
    public static function grid(array $items, int $cols = 80): void
    {
        if (empty($items)) {
            return;
        }

        $cols -= 2;
        // Calculate the longest item length
        $maxItemLength = max(array_map('mb_strlen', $items));

        // Add padding (2 spaces on each side + 1 for border)
        $cellWidth = $maxItemLength + 4;

        // Calculate how many cells can fit per row
        $cellsPerRow = max(1, (int) floor(($cols - 1) / ($cellWidth + 1)));

        // Unicode box drawing characters
        $topLeft = '╭';
        $topRight = '╮';
        $bottomLeft = '╰';
        $bottomRight = '╯';
        $horizontal = '─';
        $vertical = '│';
        $cross = '┼';
        $topT = '┬';
        $bottomT = '┴';
        $leftT = '├';
        $rightT = '┤';

        // Group items into rows
        $rows = array_chunk($items, $cellsPerRow);

        // Draw top border
        $topBorder = $topLeft;
        for ($i = 0; $i < $cellsPerRow; $i++) {
            $topBorder .= str_repeat($horizontal, $cellWidth);
            if ($i < $cellsPerRow - 1) {
                $topBorder .= $topT;
            }
        }
        $topBorder .= $topRight;
        echo ' '.$topBorder.PHP_EOL;

        // Draw rows
        $rowCount = 0;
        foreach ($rows as $row) {
            $line = $vertical;
            for ($i = 0; $i < $cellsPerRow; $i++) {
                if (isset($row[$i])) {
                    $item = $row[$i];
                    $padding = $cellWidth - mb_strlen($item) - 2;
                    $line .= ' '.$item.str_repeat(' ', $padding + 1).$vertical;
                } else {
                    // Empty cell
                    $line .= str_repeat(' ', $cellWidth).$vertical;
                }
            }
            echo ' '.$line.PHP_EOL;

            // Draw separator between rows (except after last row)
            if ($rowCount < count($rows) - 1) {
                $separator = $leftT;
                for ($i = 0; $i < $cellsPerRow; $i++) {
                    $separator .= str_repeat($horizontal, $cellWidth);
                    if ($i < $cellsPerRow - 1) {
                        $separator .= $cross;
                    }
                }
                $separator .= $rightT;
                echo ' '.$separator.PHP_EOL;
            }
            $rowCount++;
        }

        // Draw bottom border
        $bottomBorder = $bottomLeft;
        for ($i = 0; $i < $cellsPerRow; $i++) {
            $bottomBorder .= str_repeat($horizontal, $cellWidth);
            if ($i < $cellsPerRow - 1) {
                $bottomBorder .= $bottomT;
            }
        }
        $bottomBorder .= $bottomRight;
        echo ' '.$bottomBorder.PHP_EOL;
    }
}
