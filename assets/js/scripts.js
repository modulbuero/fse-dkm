(($) => {
	$(document).ready(()=>{
        animateJederKommen()
        animateFadeInMaskText()
        opencloseContact()
        
        $(document).on('nfFormReady', function() {
            //contactFormLabel()
            calculateWartungHosting()
            $(document).on('click', '.nf-next, .nf-prev', function() {
                setTimeout(function() {
                    $('#gesamtsumme').text(letzteSumme);
                }, 200);
            });
        });
        
    })

    /*Kontakt-Formular Label-Activation*/
    function contactFormLabel(){
        let labelWrap = 'form ';
        let input     = labelWrap + '.nf-field-element input';
        let textarea  = labelWrap + '.nf-field-element textarea';
        let fields    = input + ", " + textarea;
        
        function checkInputField() {
            $(fields).each(function(){
                if($(this).val() == ""){
                    $(this).parents(':eq(1)').removeClass('focus-input');
                }
            })
        }

        $(fields).on('focus click', function(){
            checkInputField();
        })

        $(fields).on('focus click', function(){
            $(this).parents(':eq(1)').addClass('focus-input');
        })

        $(fields).on('focusout', function(){
            checkInputField()
        })
    }

    function animateJederKommen(){
        let wrapper = '.jeder-kommen p.has-xx-large-font-size'
        let elements = document.querySelectorAll(wrapper)
        elements.forEach((el) => { 
            gsap.from(el, {
            filter: "blur(20px)",
            scale: 1.2,
            opacity:0,
            ease: "none",
            scrollTrigger: {
                trigger: el,
                start: "top 50%",
                end: "top 40%",
                scrub: true,
            }
            });
        })
    }

    function animateFadeInMaskText(){
        let elements = document.querySelectorAll('p.fadinmask')
        elements.forEach((el) => { 
           ScrollTrigger.create({
                trigger: el,
                start: "top 70%",
                markers: false,
                onEnter: () => {
                gsap.to(el, {
                    "--y": "100%",
                    ease: "none",
                    duration: 0.5,
                    y:-10
                });
                },
                // optional:
                onLeaveBack: () => {
                  gsap.to(el, {
                     "--y": "0%", 
                     duration: 0.5,
                     y:0
                     });
                }
            });
        })
    }

    function opencloseContact(){
        let project = '.anfrage-wrap > > div:last-child'
        $(project).on('click', function(){
            $(this).toggleClass('open')
        })
    }

    function calculateWartungHosting(){
       

            function berechneGesamtsumme() {

                var gesamt = 0;

                $('#nf-field-19 .nf-repeater-fieldset').each(function() {

                    var $fieldset = $(this);

                    // -------------------------
                    // Paket
                    // -------------------------
                    var paket = $fieldset
                        .find('input[name^="nf-field-19.2_"]:checked')
                        .val();

                    var paketPreise = {
                        'Paket-S': 180,
                        'Paket-M': 204,
                        'Paket-L': 264,
                        'Paket-XL': 312,
                        'Paket-XXL': 408
                    };

                    if (paketPreise[paket] !== undefined) {
                        gesamt += paketPreise[paket];
                    }


                    // -------------------------
                    // Wartung
                    // -------------------------
                    var wartung = $fieldset
                        .find('input[name^="nf-field-19.3_"]:checked')
                        .val();

                    var wartungPreise = {
                        'Wartung-Standard': 0,
                        'Wartung-Plus': 240,
                        'Wartung-Pro': 480
                    };

                    if (wartungPreise[wartung] !== undefined) {
                        gesamt += wartungPreise[wartung];
                    }

                });

                letzteSumme = gesamt;

                // -------------------------
                // Ausgabe
                // -------------------------
                $('#gesamtsumme').text(gesamt);
            }

            // Änderung von Paket oder Wartung
            $(document).on(
                'change',
                '#nf-field-19 input[type="radio"]',
                function() {
                    berechneGesamtsumme();
                }
            );

            // Initial berechnen
            berechneGesamtsumme();
       
    }
})(jQuery)