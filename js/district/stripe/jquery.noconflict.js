var district = district || {};
if(!window.jQuery) {
  district.$ = jQuery.noConflict();
} else {
  district.$ = jQuery;
}