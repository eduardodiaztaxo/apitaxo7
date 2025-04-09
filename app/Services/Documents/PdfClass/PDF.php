<?php

namespace App\Services\Documents\PdfClass;



class PDF extends PDF_WriteTag_Table
{

    protected $titleDocHeader;
    protected $subTitleDocHeader;
    protected $pathLogoHeader;

    // Cabecera
    function Header()
    {

        if ($this->pathLogoHeader)
            $this->Image($this->pathLogoHeader, 10, 8, 30, 14);

        if ($this->titleDocHeader) {
            $this->SetFont('Arial', '', 18);
            $this->SetTextColor(81, 166, 232);
            $this->SetX($this->getLM() + 30);
            //$this->Cell($this->widthPage - 60, 6, $this->_conv_str($titulo), 0, 1, 'C');
            $this->MultiCell($this->getFullWidth() - 60, 6, $this->titleDocHeader, 0, 'C');
        }


        if ($this->subTitleDocHeader) {
            $this->SetFont('Arial', 'B', 12);
            $this->SetTextColor(0, 0, 0);
            $this->SetY(8);
            $this->SetX($this->getFullWidth() - $this->getLM() - 10);

            $this->MultiCell(25, 6, $this->subTitleDocHeader, 0, 'R');
        }

        if ($this->subTitleDocHeader || $this->titleDocHeader || $this->pathLogoHeader)
            $this->Ln(6);
    }

    // Pie de página
    function Footer()
    {
        // Posición: a 1,5 cm del final
        $this->SetY(-15);
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        // Número de página
        $this->Cell(0, 10, '- ' . $this->PageNo() . ' -', 0, 0, 'C');
    }



    function getFullWidth()
    {

        return $this->w - $this->lMargin - $this->rMargin;
    }

    function getLM()
    {
        return $this->lMargin;
    }


    function getHeight()
    {
        return $this->h;
    }

    function getBM()
    {
        return $this->bMargin;
    }


    function setTitleDocHeader($title)
    {
        $this->titleDocHeader = $title;
    }

    function setSubTitleDocHeader($subtitle)
    {
        $this->subTitleDocHeader = $subtitle;
    }
    function setPathLogoHeader($path)
    {
        $this->pathLogoHeader = $path;
    }
}
