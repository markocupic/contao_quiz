/**
 * Created by Marko on 23.07.2017.
 */

jQuery.fn.contaoQuizStep2 = function (options) {

    // Bob's default settings:
    var defaults = {
        requestToken: '',
        sliderContainer: '.quiz-slider',
        sliderItem: '.quiz-slider-item',
        buttonPrevSlide: '.btn-prev-slide',
        buttonNextSlide: '.btn-next-slide',
        buttonAnswer: '.button-answer',
        backgroundColor: "#fff",
        fontSize: "1em",
        delay: "quite long",
        getTextFromTitle: true,
        getTextFromRel: false,
        getTextFromAlt: false,
        animateWidth: true,
        animateOpacity: true,
        animateHeight: true,
        animationDuration: 500,
        clickImgToGoToNext: true,
        clickImgToGoToLast: false,
        nextButtonText: "next",
        previousButtonText: "previous",
        nextButtonTextColor: "red",
        previousButtonTextColor: "red"
    };

    var settings = $.extend({}, defaults, options);

    return this.each(function () {
        // Plugin code goes here...
        var quizContainer = $(this);


        // Get settings from the container attribute "data-contao-quiz"
        var quizData = JSON.parse(window.atob($('.mod_quiz[data-contao-quiz]').attr('data-contao-quiz')));


        var submitContainer = $(quizContainer).find('.submit_container');


        // Show submit button from the beginning, if there is only one slide
        // Place oninit event before you instantiate the slider
        $(settings.sliderContainer).on('init', function (event, slick) {
            controlNavBtnVisibiliy(slick, submitContainer);
            if (slick.slideCount == 1) {
                $(submitContainer).removeClass('hidden');
            }
        });

        // Instantiate the slider
        $(settings.sliderContainer).slick({
            slide: settings.sliderItem,
            infinite: false,
            prevArrow: '',
            nextArrow: '',
            adaptiveHeight: true,
            accessibility: false,
            arrows: false,
            draggable: false
        });


        // Event: On after slide change
        $(settings.sliderContainer).on('afterChange', function (event, slick, currentSlide) {
            controlNavBtnVisibiliy(slick, submitContainer);
        });


        // Nav buttons
        $(settings.buttonPrevSlide).click(function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(settings.sliderContainer).slick('slickPrev');
        });

        $(settings.buttonNextSlide).click(function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(settings.sliderContainer).slick('slickNext');

        });


        // Button handling
        $(settings.buttonAnswer).click(function (e) {
            var elButton = $(this);

            // Send answer to server
            if ($(elButton).attr('data-answer') != '') {
                var url = window.location.href + '&send_answer=true';
                var data = {
                    REQUEST_TOKEN: settings.requestToken,
                    data_answer: $(elButton).attr('data-answer')
                };
                var jqxhr = $.post(url, data, function (response) {
                    console.log(response);
                    response = JSON.parse(response);
                    console.log(response);
                });
            }


            // Radio handling

            var radioId = $(elButton).attr('data-radio-id');
            var radio = $('#' + radioId);
            radio.trigger('click');
            $(elButton).closest('.question').find('.button-answer').each(function () {
                $(this).attr('aria-pressed', 'false');
                $(this).removeClass('active');
            });
            window.setTimeout(function () {
                elButton.attr('aria-pressed', 'true');
                elButton.addClass('active');
            }, 50);

            $(elButton).closest(settings.sliderItem).addClass('question-solved');
            if ($(settings.sliderItem).length == $(settings.sliderItem + '.question-solved').length) {
                $(submitContainer).removeClass('hidden')
            }
            var currentSlide = $(settings.sliderContainer).slick('slickCurrentSlide');
            $('.counter-item:eq( ' + (currentSlide) + ' )').addClass('passed');


            // Go to the next slide when the answer button was pressed!!!!
            $(settings.buttonNextSlide).trigger('click');


        });


        function controlNavBtnVisibiliy(slick, submitContainer) {
            if (slick.slideCount == slick.currentSlide + 1) {
                $(settings.buttonNextSlide).addClass('disabled');
            } else {
                $(submitContainer).addClass('hidden');
                $(settings.buttonNextSlide).removeClass('disabled');
            }
            if (slick.currentSlide == 0) {
                $(settings.buttonPrevSlide).addClass('disabled');
            } else {
                $(settings.buttonPrevSlide).removeClass('disabled');
            }
        }


    });

};





