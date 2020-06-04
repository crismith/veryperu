/*
*   Javacripts for Product page
*/
//Hides or shows custom field on product page if select "Recalculated price additional text" is set to Custom
"use strict";
jQuery(document).ready(function($){
    var selectAddRecalcText = $( 'select#_mcmp_ppu_recalc_text_override' );
    var inputRecalcTextOvr = $('p._mcmp_ppu_recalc_text_override_cust_field');
    EnableCustomRecalcTextOvr();
    selectAddRecalcText.change(function(){EnableCustomRecalcTextOvr(300)});
    function EnableCustomRecalcTextOvr(speed=0) {
        if ( selectAddRecalcText.prop("value")=='-custom-' ) {
            inputRecalcTextOvr.show(speed);
        } else {
            inputRecalcTextOvr.hide(speed);
        }
    }
});
//Shows a warning if "Custom number of units" not empty and "Different weight unit" is set to some different unit
jQuery(document).ready(function($){
    var selectDifWeight = $( 'select#_mcmp_ppu_ratio_unit_override' );
    var inputCustNumOfUnits = $( 'input#_mcmp_ppu_cust_num_of_units_override' );
    ShowDiffWeight();
    selectDifWeight.change(function(){ShowDiffWeight()});
    inputCustNumOfUnits.change(function(){ShowDiffWeight()});
    function ShowDiffWeight() {
        if ( selectDifWeight.val() != "" && inputCustNumOfUnits.val() !="") {
            $( ".admin-warn-cust-units" ).show();
        } else {
            $( ".admin-warn-cust-units" ).hide();
        }
    }
});
//Warning for no weight set
jQuery(document).ready(function($){
    var inputWeight = $( '#shipping_product_data input#_weight' );
    var inputCustNumOfUnits = $( 'input#_mcmp_ppu_cust_num_of_units_override' );
    ShowNoWeightWarn();
    inputWeight.change(function(){ShowNoWeightWarn()});
    inputCustNumOfUnits.change(function(){ShowNoWeightWarn()});
    function ShowNoWeightWarn() {
        if ( inputWeight.val() == "" && inputCustNumOfUnits.val() == "") {
            $( ".admin-warn-no-weight" ).show();
        } else {
            $( ".admin-warn-no-weight" ).hide();
        }
    }
});
/*
*   Javacripts for General settings page
*/
//Hides or shows "Preposition for weight" on general settings if select "Recalculated price additional text" is set to Automatic
jQuery(document).ready(function($){
    var selectRecalcText = $( 'select#_mcmp_ppu_recalc_text' );
    var inputRecalcPreposition = $( 'input#_mcmp_ppu_recalc_text_automatic_preposition' ).closest('tr');
    ShowPreposition();
    selectRecalcText.change(function(){ShowPreposition(300)});
    function ShowPreposition(speed=0) {
        if ( selectRecalcText.val()=='-automatic-' ) {
            inputRecalcPreposition.show(speed);
        } else {
            inputRecalcPreposition.hide(speed);
        }
    }
});
//Adds close button to dismissable info for class mcmp-notice - workaround for Woo4 - taken from Jquery, Wordpress
jQuery(document).ready(function($){
    $(".mcmp-notice.is-dismissible").each(function() {
    var t = $(this),
        e = $('<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>'),
        n = commonL10n.dismiss || "";
    e.find(".screen-reader-text").text(n),
        e.on("click.wp-dismiss-notice", function(e) {
        e.preventDefault(),
            t.fadeTo(100, 0, function() {
            t.slideUp(100, function() {
                t.remove();
            });
            });
        }),
        t.append(e);
    });
});