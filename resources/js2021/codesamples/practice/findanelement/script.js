/**
 * Note: This file is intentionally empty.
 * You can use it to test your skills at traversing the DOM using JavaScript.
 */

var strap1 = document.querySelector(".backpack__strap");
console.log(strap1);

var straps = document.querySelectorAll(".backpack__strap");
console.log(straps);

var strap2 = straps[3];
console.log(strap2);

console.log(straps[1]);

console.log(document.querySelector(".siteheader div").innerHTML);
console.log(document.querySelector(".siteheader div:last-child").innerHTML);
console.log(document.querySelector(".siteheader .site-title").innerHTML);
console.log(document.querySelector(".siteheader .site-description").innerHTML);
console.log(document.querySelector(".site-title").innerHTML);
console.log(document.querySelector(".site-description").innerHTML);
console.log(document.querySelector(".backpack__strap .leftlength"));
console.log(document.querySelector(".leftlength"));
console.log(document.querySelector(".leftlength button").innerHTML);

var strapleft1 = document.querySelector(".leftlength button");
console.log(strapleft1.innerHTML);
strapleft1.innerHTML = "UPDATE!";
document.querySelector(".leftlength button").innerHTML = "Update again!";
console.log(document.querySelector(".backpack__strap .rightlength"));
console.log(document.querySelector(".laststrap .rightlength"));

console.log(document.querySelector(".backpack__strap .leftlength:last-child"));

/* var secondleft = document.querySelector(".backpack__strap .leftlength:last-child");

console.log("Secondleft length is ",secondleft.span.innerHTML); 

var secondleft = document.querySelector(".backpack__strap .leftlength:last-child span");

console.log("Secondleft length is ",secondleft.innerHTML); */

var secondleft = document.querySelector("body article:last-child .backpack__strap span");
console.log("Label for the left strap in the second backpack is", secondleft.innerHTML);