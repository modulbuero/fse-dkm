(($) => {
	$(document).ready(()=>{
        animateJederKommen()
        animateFadeInMaskText()
        opencloseContact()
        pinLösungen()
        scrollAwardsFast()
        $(document).on('nfFormReady', function() {
            //contactFormLabel()
            calculateWartungHosting()
            $(document).on('click', '.nf-next, .nf-prev', function() {
                setTimeout(function() {
                    $('#gesamtsumme').text(letzteSumme);
                }, 200);
            });
        });
        window.addEventListener("load", () => ScrollTrigger.refresh());
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

    function pinLösungen(){
        gsap.registerPlugin(ScrollTrigger);

        const parent    = document.querySelector(".loesung-pin");
        const items     = gsap.utils.toArray(".loesung-pin > *");
        const last      = items[items.length - 1];
        //Offset für den Pin-Punkt
        const style     = getComputedStyle(parent);
        const block     = style.getPropertyValue("--mbfse--margin-bottom-block");
        const header    = style.getPropertyValue("--mbfse-header-height");       
        const offset    = parseFloat(block) + parseFloat(header);
        const offsetpx  = offset + "px";        
        //Gleiche Höhe wenn nicht mobile
        const mqMobile  = window.matchMedia("(max-width: 600px)");
        const setEqualHeights = () => {
            //Zurücksetzen, damit die natürliche Höhe gemessen wird
            items.forEach((el) => (el.style.minHeight = ""));
            // 2. Mobil: alle Kinder 100vh
            if (mqMobile.matches) {
                items.forEach((el) => (el.style.minHeight = "calc(100vh - " + offsetpx));
                return;
            }
            // Größte Höhe ermitteln
            const maxH = Math.max(...items.map((el) => el.offsetHeight));
            // Allen Kindern als Mindesthöhe setzen
            items.forEach((el) => (el.style.minHeight = maxH + "px"));
        };
        setEqualHeights();
        ScrollTrigger.addEventListener("refreshInit", setEqualHeights);

        //Scrolltrigger
        items.forEach((el, i) => {            
            // el.style.zIndex = i + 1;
            // Das letzte Kind wird nicht gepinnt, es scrollt normal und dient als Endpunkt
            if (el === last) return;

            ScrollTrigger.create({
                trigger: el,
                start: "top " + offsetpx,
                endTrigger: last,
                end: "top " + offsetpx,
                pin: true,
                pinSpacing: false,               // wichtig: sonst entstehen Lücken, die Kinder überlagern sich
                invalidateOnRefresh: true,
            });
        });
    }

    function scrollAwardsFast(){
        const wrapper = document.querySelector(".auszeichnungen-wrapper>div");
        const gallery = wrapper?.querySelector(".wp-block-gallery");
        if (!wrapper || !gallery) return;
        const mm      = gsap.matchMedia();

        const getScrollSettings = (start, end) => ({
            trigger: wrapper,
            start,
            end,
            scrub: true,
            invalidateOnRefresh: true,
            markers:false,
        });
        
        // Distanz bis das letzte Kind an der Wrapper-Kante anliegt
        const getDistance = (horizontal) => {
            const last = gallery.lastElementChild;
            if (!last) return 0;

            const w = wrapper.getBoundingClientRect();
            const l = last.getBoundingClientRect();

            if (horizontal) {
            const currentX = gsap.getProperty(gallery, "x") || 0;
            const lastRight = l.right - w.left - currentX;
            return Math.max(0, lastRight - wrapper.clientWidth);
            }

            const currentY = gsap.getProperty(gallery, "y") || 0;
            const lastBottom = l.bottom - w.top - currentY;
            return Math.max(0, lastBottom - wrapper.clientHeight);
        };

        

        // Desktop: vertikal
        mm.add("(min-width: 1025px)", () => {
            gsap.fromTo(
            gallery,
            { y: 0 },
            {
                y: () => -getDistance(false),
                ease: "none",
                scrollTrigger: getScrollSettings("top 55%", "bottom 45%")
            }
            );
        });

        // Tablet/Mobile: horizontal
        mm.add("(max-width: 1024px)", () => {
            gsap.fromTo(
            gallery,
            { x: 0 },
            {
                x: () => -getDistance(true),
                ease: "none",
                scrollTrigger: getScrollSettings("center 70%", "bottom 70%")
            }
            );
        });

        // Falls Icons nachladen (lazy loading), Werte neu berechnen
        gallery.querySelectorAll("img").forEach((img) => {
            if (!img.complete) img.addEventListener("load", () => ScrollTrigger.refresh());
        });
    }
})(jQuery)