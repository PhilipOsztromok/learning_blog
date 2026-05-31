/**
 * Note: JavaScript file for the Add an Element challenge in chapter 5 - the DOM

 */

const content = `
	<nav class="horizontalmenu"
		<ul>
			<li><a href="/webdevelopment/webdevelopment.html">Web Development</a></li>
			<li><a href="/linux/linux.html">Linux</a></li>
			<li><a href="/photography/photography.html">Photography</a></li>
			<li><a href="/programming/programming.html">Programming</a></li>
			<li><a href="/hungary/hungary.html">Hungary</a></li>
			<li><a href="/others/others.html">Others</a></li>
		</ul>
	</nav>
`;

const header = document.querySelector(".header");

const nav = document.createElement("nav");
nav.innerHTML = content;

header.append(nav);