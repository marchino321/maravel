<?php

use \Core\Classi\Chiamate;
use  \App\Config;
$logo = Config::$Logo_App;
$intestazione = Config::$LOGO_APPINTESTAZIONE_PDF;
$c = new Chiamate();
$outputAddRuls = "";
if (isset($_GET['regola']) && !empty($_GET['regola'])) {
    $regoleAggiuntive = $c->seleziona("ap_regole_add", "idMainRegola", $_GET['regola']);
    $regola = $c->seleziona("tbl_regole", "idRegolaMainAutoIncrement", $_GET['regola']);
    if (!empty($regola)) {
        if (!empty($regoleAggiuntive)) {
            $outputAddRuls .= '<table cellspacing="0" style="width: 100%; text-align: center; font-size: 14px;padding: 5px;">
                <tr>
                    <th style="border-bottom: 1px solid black; padding-bottom: 10px;">Tipo</th>
                    <th style="border-bottom: 1px solid black; padding-bottom: 10px;">Percentuale</th>
                    <th style="border-bottom: 1px solid black; padding-bottom: 10px;">Preavviso</th>

                </tr>
            ';
            foreach ($regoleAggiuntive as $add) {
                $tipoPenalita = "Full";
                switch ($add['tipologiaPenalita']) {
                    case '2':
                        $tipoPenalita = "Parziale";
                        break;
                    case '3':
                        $tipoPenalita = "No Charge";
                        break;
                    default:
                        $tipoPenalita = "Full";
                        break;
                }
                $tempo = "Minuti";
                switch ($add['tipologiaTempo']) {
                    case '1':
                        $tempo = "Ore";
                        break;
                    case '2':
                        $tempo = "Giorni";
                        break;
                    default:
                        $tempo = "Minuti";
                        break;
                }
                $outputAddRuls .= "<tr >";
                $outputAddRuls .= "<td style='width: 33.33%;border-bottom:1px solid black; padding:10px'>" . $tipoPenalita . "</td>";
                $outputAddRuls .= "<td style='width: 33.33%;border-bottom:1px solid black; padding:10px'>" . $add['percentualeCharge'] . "%</td>";
                $outputAddRuls .= "<td style='width: 33.33%;border-bottom:1px solid black; padding:10px'>" . $add['minutiSetting'] . " - $tempo</td>";

                $outputAddRuls .= "</tr>";
            }
            $outputAddRuls .= '</table>';
        }

        $noShow = "<br /><div style='border-left:1px solid black;padding:20px;margin-top:20px;margin-left:10px'><p>In caso di no-show, verrà addebitata una penale del <font color='red'>" . $regola[0]['percentualePenalita'] . "%</font> sul totale del servizio prenotato. Il no-show si verifica trascorsi <font color='red'>" . $regola[0]['gracePeriod'] . " minuti</font> dall’orario stabilito.</p></div>";





        $testo = str_replace(["%BLOCCO_REGOLE%", "%BLOCCO_NOSHOW%"], [$outputAddRuls, $noShow], $regola[0]['baseContratto']);
    } else {
        echo "Contratto non trovato";
        exit;
    }
} else {
    echo "Contratto non trovato";
    exit;
}
?>
<style>
    p {
        margin: 0;
        padding: 0;
    }
</style>
<page backcolor="#FEFEFE" backimgx="center" backimgy="bottom" backimgw="100%" backtop="0" backbottom="30mm" footer="date;time;page" style="font-size: 12pt">
    <bookmark title="Lettre" level="0"></bookmark>
    <table cellspacing="0" style="width: 100%; text-align: center; font-size: 14px">
        <tr>
            <td style="width: 25%; color: #444444;">
                <img style="width: 70%;" src="<?php echo $_SERVER['DOCUMENT_ROOT'] ?><?php echo $logo; ?>" alt="Logo"><br>
            </td>
            <td style="width: 75%;">
                <table cellspacing="0" style="width: 100%; text-align: right; font-size: 11pt;">
                    <tr>
                        <td style="width:30%;"></td>
                        <td style="width:1%; "></td>
                        <td style="width:69%"><?php echo $intestazione; ?></td>
                    </tr>

                </table>
            </td>

        </tr>
    </table>
    <table cellspacing="0" style="width: 100%;margin-top: 50px;">
        <tr>
            <td style="width:100%;"><?php echo $testo; ?></td>
        </tr>
    </table>
</page>