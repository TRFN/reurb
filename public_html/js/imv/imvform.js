LWDKInitFunction.addFN((styleForm=()=>{
	window.counter = 0;
	$(".row.category:not(.m--hide)").each(function(){
		counter%2 == 1
			? $(this).addClass("bg-light text-black p-4").css({borderRadius: "8px"})
			: $(this).addClass("bg-primary text-white p-4").css({borderRadius: "8px"});
		counter++;
	});
}));

LWDKExec(()=>{

	styleForm();
    window.getImv = function () {
        return MapKeyAssign(
            MapEl(
                $(".imovel [data-name]"),
                function () {
                    return [$(this).data("name"), $(this).val()];
                },
                !1,
                !1
            )
        );
    };

    window.getId = function () {
        return "{myid}";
    };

    window.setValues = function setValues(data, to = "html", repeater = false) {
        if (repeater) {
            for (let j = 0; j < data.length - 1; j++) {
                $("[data-repeater-create]").each(function () {
                    this.click();
                });
            }
        }

        for (let j = 0; j < data.length; j++) {
            for (let k in data[j]) {
                if (repeater) {
                    (e=$("[data-repeater-item]")
                        .eq(j)
                        .find('[data-name="' + k + '"]'))
                        .val(data[j][k]);
                } else {
                    (e=$(to + ' [data-name="' + k + '"]')).val(data[j][k]);
                }

				if(e.hasClass("m_selectpicker")){
					e.selectpicker('val', data[j][k]);
				}
            }
        }
    };

	window.atualizar_valor_final = function () {
        function mtf(valor) {
            if (valor === "") {
                valor = 0;
            } else {
                valor = valor.replace(".", "");
                valor = valor.replace(",", ".");
                valor = valor.split(/[A-z\$]/).join("");
                // console.log(valor);
                valor = parseFloat(valor);
                // console.log(valor);
            }
            return valor;
        }

		function mny(_in){
			return _in.toLocaleString("pt-br", { style: "currency", currency: "BRL" });
		}

		function get(of,the){
			return $(of).find(`[data-name="${the}"]`);
		}

		imov = MapKeyAssign(
			MapEl(
				$("[data-name]"),
				function () {
					return [$(this).data("name"), $(this).val()];
				},
				!1,
				!1
			)
		);

		// console.log(imov);

        $.post("/vendedores_comissao/", {v : imov["vendedor"]}, function(comissao){
			let venda = mtf(imov["valor-venda"]),
				parc  = imov["forma-pgto"].split(/[^0-9]/).join('');

			parc = parseInt(parc);
			if(parc == 0){
				parc = venda;
			} else {
				comissao = venda / 100 * (parseInt(comissao));
				final = venda;
				parc = final / parc;
			}

			get(".row", "valor-parcela").val(mny(parc));
			get(".row", "valor-final").val(mny(final));
		});
    };

    $(".submit").click(function(){
        let data = [getId(), getImv(), "{modo}"];
        $.post("/ajax_imoveis/", { data: data }, function (success) {
            if (success === true) {
                successRequest(() => (window.scrollTo(0, 0), "{modo}" == "mod" ? null : (top.location.href = "/vendedor/")), "O imovel foi " + ("{modo}" == "mod" ? "atualizado" : "criado") + " com sucesso!");
            } else {
                console.log(success);
                errorRequest();
            }
        });
    });

	if("{modo}" == "mod"){
		setValues({imovel});
	}
});
