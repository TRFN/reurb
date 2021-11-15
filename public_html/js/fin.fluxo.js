window.f_checksum = "";

window.mudar_status = function(ctx){
	// console.log(ctx);
	$.post("/change_status/", {data: ctx}, ()=>$(".m-tooltip").remove());
};

window.apiDate = function(input){
    this.backup = input;
    this.input = new Date(typeof input !== "string" ? input : (input + "T12:00:00"));
    this.time = function(){ return this.input.getTime(); };
    this.get = function(){return this.input;};
    this._model = function(fn,val=1,op=1){
        return(this.input["set" + fn](this.input["get" + fn]() + (val*op)));
    };

    this.sumDay = function(q=1){
        return new Date(this._model("Date", q, 1));
    };

    this.sumMonth = function(q=1){
        return new Date(this._model("Month", q, 1));
    };

    this.sumYear = function(q=1){
        return new Date(this._model("Month", q*12, 1));
    };

    this.subDay = function(q=1){
        return new Date(this._model("Date", q, -1));
    };

    this.subMonth = function(q=1){
        return new Date(this._model("Month", q, -1));
    };

    this.subYear = function(q=1){
        return new Date(this._model("Month", q*12, -1));
    };
};

window.ano_atual = "{year}";

window.menor_data = [-1,-1];
window.maior_data = [-1,-1];

window.finupdt = ((callback=-1, args=-1)=>{
	args === -1 && (args = {});

	function g_money(data){
		return parseFloat(data.split(/[^0-9\,]/).join('').split(",").join("."));
	}

	function s_money(data){
		return data.toLocaleString('pt-br',{style: 'currency', currency: 'BRL'});
	}

	function ksort(obj){
	  var keys = Object.keys(obj).sort(function(a, b){return b-a})
	    , sortedObj = {};

	  for(var i in keys) {
	    sortedObj[4000-parseInt(keys[i])] = obj[keys[i]];
	  }
	  // console.log(sortedObj);
	  return Object.values(sortedObj);
	}

	$(".nav-item.nav-link:not(.mes-atual)").css({"font-weight": "400"});
	$(".nav-item.nav-link.active:not(.mes-atual)").css({"font-weight": "bold"});

	function proccess_data(data){
		if(data.checksum == f_checksum){
			return setTimeout(finupdt, 1500);
		}

		function atualizarTabelas(){
			for(let i = 1; i < 13; i++){
				let varr = "DataTable__" + (table_html=$(".mes-"+String(i)).find("table")).attr("id").split(/[^a-z]/).join('_');
				// console.log(varr);
				table = window[varr];

				$(".emdia-" + String(i)).text("0");
				$(".devendo-" + String(i)).text("0");

				table.rows().remove().draw(true);
			}
		}

		f_checksum = data.checksum;
		let anos = [];

		atualizarTabelas();

		for(dado of data.valores){
			cliente = dado[0];
			conjuge = dado[1];
			imoveis = dado[2];

			for(imovel of imoveis){
				parcelas = parseInt(imovel["forma-pgto"].split(/[^0-9]/).join(""));
				data = (new apiDate(imovel.data));
				for(k = 0; k < parcelas; k++){
					dt = data.get();
					mes = dt.getMonth() + 1;
					// console.log(mes);
					let varr = "DataTable__" + (table_html=$(".mes-" + String(mes)).find("table")).attr("id").split(/[^a-z]/).join('_');
					// console.log(varr);
					let table = window[varr], _ano,
						dta = ("0" + dt.getDate()).slice(-2) + "/" +
							  ("0"+(dt.getMonth()+1)).slice(-2) + "/" +
							  (_ano=dt.getFullYear());

					anos.indexOf(_ano) < 0 && anos.push(_ano);

					let vz = imovel["forma-pgto"].split(/[^0-9]/).join('') + "x";
					let nparc = k+1;
					nparc = nparc < 10 ? "0" + String(nparc):nparc;

					parctxt = (typeof imovel.pgtos[dta] === "undefined" || imovel.pgtos[dta] != "pg") ? '<div class="d-block text-center mb-3 " data-skin="white" data-toggle="m-tooltip" data-placement="left" title="" data-original-title="Mudar para Pago"><span onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',1]); return false;" class="mt-3 text-uppercase badge badge-danger p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">Pendente</span><a class=d-block onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',1]); return false;">(Alterar)</a></div>'
					: '<div class="d-block text-center mb-3 " data-skin="white" data-toggle="m-tooltip" data-placement="left" title="" data-original-title="Mudar para Pendente"><span onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',0]); return false;" class="mt-3 text-uppercase badge badge-success p-2" style="font-size: 12px; cursor: pointer; border-radius: 3px; text-shadow: 0px 0px 1px #0005;">Pago</span><a class=d-block onclick="mudar_status(['+cliente.id+','+imovel.vendedor.id+','+imovel.id+',\''+dta+'\',0]); return false;">(Alterar)</a></div>';

					let estaparc = imovel["valor-parcela-"+String(k+1)];

					String(ano_atual) == String(_ano) && table.row.add(d=[
						`<strong class="data-tabela-ano-${_ano}">${dta}</strong>`,
						`<a target=_blank href="/cliente/editar/${cliente.id}/" href="" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="${cliente.nome}">${cliente.nome.substr(0, 12)}...</a>`,
						`<b>Vendedor:&nbsp;</b><a target=_blank href="/vendedor/editar/${imovel.vendedor.id}/" href="" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="${imovel.vendedor.nome}">${imovel.vendedor.nome.substr(0, 18)}&nbsp;...</a>
						<br>
						<b>Imóvel:&nbsp;</b><a target=_blank href="/imovel/editar/${imovel.id}/" href="" data-skin="white" data-toggle="m-tooltip" data-placement="top" title="" data-original-title="${imovel.rua}, ${imovel.numero}, ${imovel.bairro}">${imovel.rua.substr(0, 18)}&nbsp;...</a>
						<br>
						<b>Produto:&nbsp;</b>${imovel.produto}`,
						`${imovel["mtd-pgto"]},&nbsp;de ${vz}&nbsp;<br><b>Valor:</b>&nbsp;${estaparc}<br>Parcela&nbsp;${nparc}`,
						parctxt
					]).draw( true );

					$("[data-parc=" + String(k+1) + "] .status").html(parctxt);
				data.sumMonth();
				}
			}
		}
		let anos_html = "";
		for(let _ano of anos){
			let sl = ano_atual == _ano ? 'selected="selected" ':'';
			anos_html += `<option ${sl} value=${_ano}>&nbsp;${_ano}&nbsp;</option>`;
		}

		$("#anos").html(anos_html);

		LWDKInitFunction.exec();

		setTimeout(finupdt, 250);
	}

	$.post("/pagamentos_data/", args, callback===-1 ? proccess_data:callback);
});

LWDKExec(()=>setTimeout(()=>(finupdt(), setTimeout(()=>$("#anos").change(function(){
	if($(this).val() === false)return;

	ano_atual = $(this).val();
	$(".ano").text(ano_atual);
	f_checksum = "upt";
}),1000)), 600));

LWDKExec(()=>$('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
   $($.fn.dataTable.tables(true)).DataTable()
      .columns.adjust();
}));

LWDKExec(()=>($('.animated-btn > i').css({"transform":"scale(1.2)","transition":"all 600ms ease"}),$('.animated-btn').css({"border-radius":"2.5rem"}).animate({opacity:1}), setTimeout(()=>$('.animated-btn').each(function(i){
	$(this).find("> span").each(function(){
		$(this).data("orig-width", $(this).width());
	});
	let t = 150;
	$(this).find("> span").delay($(this).data("anim-delay")).animate({"width":"0px"}, t);
	$(this).find("> i").delay($(this).data("anim-delay")*2).css({"transform":"scale(1.6)"});
	$(this).delay($(this).data("anim-delay")).animate({"border-radius": "100%"}, t);
	// $(this).find("> i").css({"font-size": "18px"});
	$(this).mouseenter(function(){
		$(this).find("> span").css({"width":$(this).find("> span").data("orig-width")});
		$(this).css({"border-radius": "2.5rem"});
		$(this).find("> i").css({"transform":"scale(1.2)"});
	}).mouseleave(function(){
		$(this).find("> span").animate({"width":"0px"}, 300, ()=>($(this).animate({"border-radius": "100%"}, 100),$(this).find("> i").css({"transform":"scale(1.6)"})));
	});
}),1800)));
