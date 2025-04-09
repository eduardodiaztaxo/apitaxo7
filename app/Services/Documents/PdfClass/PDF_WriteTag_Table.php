<?php

namespace App\Services\Documents\PdfClass;




class PDF_WriteTag_Table extends PDF_WriteTag
{

    protected $ProcessingTable = false;
    protected $aCols = array();
    protected $TableX;
    protected $HeaderColor;
    protected $RowColors;
    protected $ColorIndex;
    protected $printHeader = false;

    protected $font = 'Arial';

    public function setTableFont($font, $file, $dir)
    {
        $this->AddFont($font, '', $file, $dir);

        $this->font = $font;
    }

    function TableHeader($fontSize = 12)
    {
        if ($this->printHeader) {

            $this->SetFont($this->font, 'B', $fontSize);
            $this->SetX($this->TableX);
            $fill = !empty($this->HeaderColor);
            if ($fill)
                $this->SetFillColor($this->HeaderColor[0], $this->HeaderColor[1], $this->HeaderColor[2]);
            foreach ($this->aCols as $col) {
                $title = !empty($col['t']) ? $col['t'] : $col['c'];
                $this->Cell($col['w'], 6, $title, 1, 0, 'C', $fill);
            }
            $this->Ln();
        }
    }

    function printHeader(bool $print)
    {

        $this->printHeader = $print;
    }

    function resetCols()
    {

        $this->aCols = array();
    }

    function Row($data)
    {
        $this->SetX($this->TableX);
        $ci = $this->ColorIndex;
        $fill = !empty($this->RowColors[$ci]);
        if ($fill)
            $this->SetFillColor($this->RowColors[$ci][0], $this->RowColors[$ci][1], $this->RowColors[$ci][2]);



        foreach ($this->aCols as $col) {

            if (isset($col['textStyleCell']))
                $this->SetFont($this->font, $col['textStyleCell']);

            if (isset($col['color']))
                $this->SetTextColor($col['color'][0], $col['color'][1], $col['color'][2]);

            $this->Cell($col['w'], $col['hcell'], $data[$col['c']], $col['linecell'], 0, $col['a'], $fill);
        }
        $this->Ln();
        $this->ColorIndex = 1 - $ci;
    }



    function RowMulti($data)
    {


        $this->SetX($this->TableX);
        $ci = $this->ColorIndex;
        $fill = !empty($this->RowColors[$ci]);
        if ($fill)
            $this->SetFillColor($this->RowColors[$ci][0], $this->RowColors[$ci][1], $this->RowColors[$ci][2]);




        // Calculate the height of the row
        $nb = 0;

        foreach ($this->aCols as $col) {

            if (isset($col['textStyleCell']))
                $this->SetFont($this->font, $col['textStyleCell']);


            //Si viene img se calcula como vacio
            $calc_data = isset($data[$col['c']]['img']) ? ' ' : $data[$col['c']];


            $hcell = $col['hcell'];
            $nb = max(
                $nb,
                is_array($calc_data) ? $this->CalcHeightListCell($calc_data, $col['w']) : $this->NbLines($col['w'], $calc_data)
            );
        }

        $h = $hcell * $nb;


        // Issue a page break first if needed
        $this->CheckPageBreak($h);


        foreach ($this->aCols as $col) {




            $x = $this->GetX();
            $y = $this->GetY();
            // Draw the border
            if (!isset($col['linecell']) || $col['linecell'] > 0)
                $this->Rect($x, $y, $col['w'], $h);

            if (isset($col['color']))
                $this->SetTextColor($col['color'][0], $col['color'][1], $col['color'][2]);

            if (is_array($data[$col['c']])) {
                if (isset($data[$col['c']]['img'])) {

                    $imgObj = $data[$col['c']]['img'];

                    $pathimg = $imgObj['path'];
                    $wimg = isset($imgObj['w']) ? $imgObj['w'] : 14;
                    $himg = isset($imgObj['h']) ? $imgObj['h'] : 14;

                    $this->MultiCell($col['w'], $col['hcell'], ' ', 0, $col['a']);
                    $this->Image($pathimg, $x, $y, $wimg, $himg);
                } else {
                    $this->MultiCellList($col['w'], $col['hcell'], $x, $y, $data[$col['c']], $col['a']);
                }
            } else
                $this->MultiCell($col['w'], $col['hcell'], $data[$col['c']], 0, $col['a']);

            $this->SetXY($x + $col['w'], $y);
        }

        $this->ColorIndex = 1 - $ci;




        // Go to the next line
        $this->Ln($h);
    }

    function MultiCellList($w, $h, $x, $y, array $list, $align)
    {
        foreach ($list as $item) {
            $this->SetXY($x, $y);
            $this->MultiCell($w, $h, $item, 0, $align);
            $y = $y + $this->NbLines($w, $item) * $h;
        }
    }

    function CalcHeightListCell(array $list, $width)
    {
        $nb = 0;
        foreach ($list as $item) {
            $nb = $nb + $this->NbLines($width, $item);
        }

        return $nb;
    }


    function CheckPageBreak($h)
    {
        // If the height h would cause an overflow, add a new page immediately
        if ($this->GetY() + $h > $this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w, $txt)
    {
        // Compute the number of lines a MultiCell of width w will take
        if (!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $cw = $this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }

    function CalcWidths($width, $align)
    {
        // Compute the widths of the columns
        $TableWidth = 0;
        foreach ($this->aCols as $i => $col) {
            $w = $col['w'];
            if ($w == -1)
                $w = $width / count($this->aCols);
            elseif (substr($w, -1) == '%')
                $w = $w / 100 * $width;
            $this->aCols[$i]['w'] = $w;
            $TableWidth += $w;
        }
        // Compute the abscissa of the table
        if ($align == 'C')
            $this->TableX = max(($this->w - $TableWidth) / 2, 0);
        elseif ($align == 'R')
            $this->TableX = max($this->w - $this->rMargin - $TableWidth, 0);
        else
            $this->TableX = $this->lMargin;
    }

    function SetHeightCell($height)
    {

        foreach ($this->aCols as $i => $col) {

            $this->aCols[$i]['hcell'] = $height;
        }
    }

    function SetLineCell($line)
    {

        foreach ($this->aCols as $i => $col) {

            $this->aCols[$i]['linecell'] = $line;
        }
    }

    function SetLineCellPartial(array $line)
    {
        foreach ($this->aCols as $i => $col) {

            $this->aCols[$i]['linecell'] = $line[$i];
        }
    }


    function SetTextStyleCellPartial(array $style)
    {
        foreach ($this->aCols as $i => $col) {

            $this->aCols[$i]['textStyleCell'] = $style[$i];
        }
    }

    function AddCol($field = -1, $width = -1, $caption = '', $align = 'L', $title = '', $color = null)
    {
        // Add a column to the table
        if ($field == -1)
            $field = count($this->aCols);
        $this->aCols[] = array('f' => $field, 'c' => $caption, 'w' => $width, 'a' => $align, 't' => $title, 'color' => $color);
    }

    function Table($data, $prop = array())
    {
        // Execute query

        // Add all columns if none was specified
        if (count($this->aCols) == 0) {
            $nb = count($data[0]);
            for ($i = 0; $i < $nb; $i++)
                $this->AddCol();
        }
        // Retrieve column names when not specified
        foreach ($this->aCols as $i => $col) {
            if ($col['c'] == '') {
                if (is_string($col['f']))
                    $this->aCols[$i]['c'] = ucfirst($col['f']);
                else
                    $this->aCols[$i]['c'] = array_keys($data[0])[$col['f']];
            }
        }

        // Handle properties
        if (!isset($prop['width']))
            $prop['width'] = 0;
        if ($prop['width'] == 0)
            $prop['width'] = $this->w - $this->lMargin - $this->rMargin;

        if (!isset($prop['hcell']))
            $prop['hcell'] = 0;
        if ($prop['hcell'] == 0)
            $prop['hcell'] = 5;

        if (!isset($prop['linecell']))
            $prop['linecell'] = 0;


        if (!isset($prop['align']))
            $prop['align'] = 'C';
        if (!isset($prop['padding']))
            $prop['padding'] = $this->cMargin;
        $cMargin = $this->cMargin;
        $this->cMargin = $prop['padding'];
        if (!isset($prop['HeaderColor']))
            $prop['HeaderColor'] = array();
        $this->HeaderColor = $prop['HeaderColor'];
        if (!isset($prop['color1']))
            $prop['color1'] = array();
        if (!isset($prop['color2']))
            $prop['color2'] = array();
        $this->RowColors = array($prop['color1'], $prop['color2']);
        // Compute column widths
        $this->CalcWidths($prop['width'], $prop['align']);
        $this->SetHeightCell($prop['hcell']);

        if (is_array($prop['linecell']))
            $this->SetLineCellPartial($prop['linecell']);
        else
            $this->SetLineCell($prop['linecell']);


        if (isset($prop['textStyleCell']) && is_array($prop['textStyleCell'])) {
            $this->SetTextStyleCellPartial($prop['textStyleCell']);
        }


        if (!isset($prop['fontsize']))
            $prop['fontsize'] = 11;

        // Print header
        $this->TableHeader($prop['fontsize']);
        // Print rows


        $this->SetFont($this->font, '', $prop['fontsize']);
        $this->ColorIndex = 0;
        $this->ProcessingTable = true;

        if (!isset($prop['multicell']))
            $prop['multicell'] = false;

        foreach ($data as $row) {
            if ($prop['multicell'] === false)
                $this->Row($row);
            else
                $this->RowMulti($row);
        }

        $this->ProcessingTable = false;
        $this->cMargin = $cMargin;
        $this->aCols = array();
    }

    function RoundedRect($x, $y, $w, $h, $r, $corners = '1234', $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));

        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        if (strpos($corners, '2') === false)
            $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $y) * $k));
        else
            $this->_Arc($xc + $r * $MyArc, $yc - $r, $xc + $r, $yc - $r * $MyArc, $xc + $r, $yc);

        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        if (strpos($corners, '3') === false)
            $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - ($y + $h)) * $k));
        else
            $this->_Arc($xc + $r, $yc + $r * $MyArc, $xc + $r * $MyArc, $yc + $r, $xc, $yc + $r);

        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - ($y + $h)) * $k));
        if (strpos($corners, '4') === false)
            $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - ($y + $h)) * $k));
        else
            $this->_Arc($xc - $r * $MyArc, $yc + $r, $xc - $r, $yc + $r * $MyArc, $xc - $r, $yc);

        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $yc) * $k));
        if (strpos($corners, '1') === false) {
            $this->_out(sprintf('%.2F %.2F l', ($x) * $k, ($hp - $y) * $k));
            $this->_out(sprintf('%.2F %.2F l', ($x + $r) * $k, ($hp - $y) * $k));
        } else
            $this->_Arc($xc - $r, $yc - $r * $MyArc, $xc - $r * $MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c ',
            $x1 * $this->k,
            ($h - $y1) * $this->k,
            $x2 * $this->k,
            ($h - $y2) * $this->k,
            $x3 * $this->k,
            ($h - $y3) * $this->k
        ));
    }




    function circledNumber($number, $x, $y, $diameter = 10)
    {
        $this->SetFont($this->font, 'B', 10);
        $this->SetXY($x, $y);
        $this->Circle($x + $diameter / 2, $y + $diameter / 2, $diameter / 2, 0, 360);
        $this->Cell($diameter, $diameter, $number, 0, 0, 'C');
    }

    function Circle($x, $y, $r, $style = 'D')
    {
        $this->Ellipse($x, $y, $r, $r, $style);
    }

    function Ellipse($x, $y, $rx, $ry, $style = 'D')
    {
        if ($style == 'F')
            $op = 'f';
        elseif ($style == 'FD' || $style == 'DF')
            $op = 'B';
        else
            $op = 'S';
        $lx = 4 / 3 * (M_SQRT2 - 1) * $rx;
        $ly = 4 / 3 * (M_SQRT2 - 1) * $ry;
        $k = $this->k;
        $h = $this->h;
        $this->_out(sprintf(
            '%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x + $rx) * $k,
            ($h - $y) * $k,
            ($x + $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x + $lx) * $k,
            ($h - ($y - $ry)) * $k,
            $x * $k,
            ($h - ($y - $ry)) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $lx) * $k,
            ($h - ($y - $ry)) * $k,
            ($x - $rx) * $k,
            ($h - ($y - $ly)) * $k,
            ($x - $rx) * $k,
            ($h - $y) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c',
            ($x - $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x - $lx) * $k,
            ($h - ($y + $ry)) * $k,
            $x * $k,
            ($h - ($y + $ry)) * $k
        ));
        $this->_out(sprintf(
            '%.2F %.2F %.2F %.2F %.2F %.2F c %s',
            ($x + $lx) * $k,
            ($h - ($y + $ry)) * $k,
            ($x + $rx) * $k,
            ($h - ($y + $ly)) * $k,
            ($x + $rx) * $k,
            ($h - $y) * $k,
            $op
        ));
    }
}
