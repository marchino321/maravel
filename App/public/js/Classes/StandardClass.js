
class Soldi {
  constructor(valore) {
    this.valore = valore;
  }
  MostraSoldi(){

    var numero = Number(this.valore).toLocaleString("es-ES", {minimumFractionDigits: 2});
  	return '€ ' + numero;
  }
  SalvaSoldi(){
    var val = this.valore;
    val = val.replace(/\./g, '');
	  val = val.replace(',', '.');
		val = val.replace('€', '');
		val = val.replace(' ', '');
    return val;
  }
}

class Percentuali {
	constructor(valore) {
		this.valore = valore;
	}

	// Mostra la percentuale in formato "10,50%"
	MostraPercentuale() {
		let numero = Number(this.valore);
		if (isNaN(numero)) {
			throw new Errori("Valore percentuale non valido");
		}
		// uso it-IT per avere la virgola come separatore decimale
		return '% ' + numero.toLocaleString("it-IT", { minimumFractionDigits: 2 });
	}

	// Sanifica e prepara per il salvataggio in database (decimal 10,2)
	SalvaPercentuale() {
		let val = this.valore;

		// Rimuovo il simbolo percentuale e spazi
		val = val.replace('%', '').trim();

		// Converto separatore decimale (virgola → punto)
		val = val.replace(',', '.');

		// Tolgo eventuali punti usati come separatore migliaia
		val = val.replace(/\./g, '');

		// Converto in numero valido
		let numero = parseFloat(val);
		if (isNaN(numero)) {
			throw new Errori("Percentuale non valida");
		}

		return numero.toFixed(2); // esempio: "10.50"
	}
}