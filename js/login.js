var kolomUser = document.getElementById('l-user');
var kolomPass = document.getElementById('l-pass');

if (kolomUser.value === '') {
    kolomUser.focus();
} else {
    kolomPass.focus();
}
