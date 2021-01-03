<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Codedge\Fpdf\Fpdf\Fpdf;

class Pdf extends Fpdf
{
    public $widths;
    public $aligns;
    
    public function SetWidths($w)
    {
        //Set the array of column widths
        $this->widths=$w;
    }

    public function SetAligns($a)
    {
        //Set the array of column alignments
        $this->aligns=$a;
    }

    public function Row($data, $fill, $border=1)
    {
        //Calculate the height of the row
        $nb=0; $nbMax=0; $noComment = false; $teacherCol = 7; $teacherInitialOffset = 75; $passmark = 50;
        
        for($i=0;$i<count($data);$i++)
            if($i != $teacherCol) $nbMax=max($nbMax,$this->NbLines($this->widths[$i],$data[$i]));
        $h=5*$nbMax;
        
        //Issue a page break first if needed
        $this->CheckPageBreak($h);
        //Draw the cells of the row
        for($i=0;$i<count($data);$i++)
        {
            if($i != $teacherCol) $nb=$this->NbLines($this->widths[$i],$data[$i]);
            if($nb == 0) $nb = 1;
            if($i != $teacherCol) $w=$this->widths[$i];
            if($i != $teacherCol)$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            //Save the current position
            $x=$this->GetX();
            $y=$this->GetY();
            //set mark to red
            if($i == 2 && is_numeric($data[$i]) && $data[$i] < $passmark) $this->SetTextColor(255, 0, 0);
            //Print the text
            if($i == $teacherCol ){                
                $this->SetFont('Times','BI','10');
                if($data[$teacherCol-1] == "\n\t")
                    $this->Text($this->GetX() - $teacherInitialOffset, $this->GetY() + (bcdiv($h,$nb) + 1),$data[$i]);
                else $this->Text($this->GetX() - $teacherInitialOffset, $this->GetY() + (bcdiv($h,$nb) * $nbMax) - 2," ".$data[$i]);
                $this->SetFont('Times','','10');
            }else{
                // if(count($data) == 7 && ( $i == 2 || $i == 3 ))
                // {
                //     $this->SetFillColor(240, 240, 240); 
                //     $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],1,$a,true);  
                // }
                // else
                // {
                //     //$this->SetFillColor(220, 220, 220); 
                //     $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],1,$a,$fill);  
                // }               
                $this->MultiCell($w,bcdiv($h,$nb,1),$data[$i],$border,$a,$fill);            
            }  
            
            $this->SetTextColor(0, 0, 0);           
            
            //Put the position to the right of the cell
            $this->SetXY($x+$w,$y);
        }
        //Go to the next line
        $this->Ln($h);
    }

    private function CheckPageBreak($h)
    {
        //If the height h would cause an overflow, add a new page immediately
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    private function NbLines($w,$txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n")
            {
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax)
            {
                if($sep==-1)
                {
                    if($i==$j)
                        $i++;
                }
                else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }
            else
                $i++;
        }
        return $nl;
    }
}
