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
    window.getVen = function () {
        return MapKeyAssign(
            MapEl(
                $(".ven[data-name]"),
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

    $(".submit").click(function(){
        let data = [getId(), getVen(), "{modo}"];
        $.post("/ajax_vendedores/", { data: data }, function (success) {
            if (success === true) {
                successRequest(() => (window.scrollTo(0, 0), "{modo}" == "mod" ? null : (top.location.href = "/vendedor/")), "O vendedor foi " + ("{modo}" == "mod" ? "atualizado" : "criado") + " com sucesso!");
            } else {
                console.log(success);
                errorRequest();
            }
        });
    });

	if("{modo}" == "mod"){
		setValues({vendedor});
	}
});
