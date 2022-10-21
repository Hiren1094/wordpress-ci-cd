/* Search Form Toggle */
jQuery(document).ready(function() {
	jQuery(".header-search-toggle").click(function() {
	   jQuery(".header-search-form").toggle();
	   jQuery(".header-search-form .search-field").focus();
	 });
});

/*Mobile Menu Handle*/
jQuery(document).ready(function () {
/* MOBILE TOGLLE MENU HANDLE*/
var menuFocus, navToggleItem, focusBackward;
var menuToggle = document.querySelector('.menu-toggle');
var navMenu = document.querySelector('.nav-menu');
var navMenuLinks = navMenu.getElementsByTagName('a');
var navMenuListItems = navMenu.querySelectorAll('li');
var nav_lastIndex = navMenuListItems.length - 1;
var navLastParent = document.querySelectorAll('.main-navigation > ul > li').length - 1;

document.addEventListener('menu_focusin', function () {
    menuFocus = document.activeElement;
    if (navToggleItem && menuFocus !== navMenuLinks[0]) {
        document.querySelectorAll('.main-navigation > ul > li')[navLastParent].querySelector('a').focus();
    }
    if (menuFocus === menuToggle) {
        navToggleItem = true;
    } else {
        navToggleItem = false;
    }
}, true);


document.addEventListener('keydown', function (e) {
    if (e.shiftKey && e.keyCode == 9) {
        focusBackward = true;
    } else {
        focusBackward = false;
    }
});


for (el of navMenuLinks) {
    el.addEventListener('blur', function (e) {
        if (!focusBackward) {
            if (e.target === navMenuLinks[nav_lastIndex]) {
                menuToggle.focus();
            }
        }
    });
}
menuToggle.addEventListener('blur', function (e) {
    if (focusBackward) {
        navMenuLinks[nav_lastIndex].focus();
    }
});


});