var wu_check_pass_strength = function wu_check_pass_strength(_pass1, _pass2) {

    var strength,
        pass1 = jQuery(_pass1).val(),
        pass2 = jQuery(_pass2).val();

    // Reset classes and result text
    jQuery('#pass-strength-result').removeClass('short bad good strong');
    if (!pass1) {
        jQuery('#pass-strength-result').html(pwsL10n.unknown);
        return;
    }

    strength = wp.passwordStrength.meter(pass1, wp.passwordStrength.userInputBlacklist(), pass2);

    switch (strength) {
        case 2:
            jQuery('#pass-strength-result').addClass('bad').html(pwsL10n.bad);
            break;
        case 3:
            jQuery('#pass-strength-result').addClass('good').html(pwsL10n.good);
            break;
        case 4:
            jQuery('#pass-strength-result').addClass('strong').html(pwsL10n.strong);
            break;
        case 5:
            jQuery('#pass-strength-result').addClass('short').html(pwsL10n.mismatch);
            break;
        default:
            jQuery('#pass-strength-result').addClass('short').html(pwsL10n.short);
            break;
    }
};