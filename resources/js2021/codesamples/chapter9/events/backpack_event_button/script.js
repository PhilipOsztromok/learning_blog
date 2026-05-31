/**
 * Event listeners
 * @link https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
 * @link https://developer.mozilla.org/en-US/docs/Web/Events
 */
import backpackObjectArray from "./components/data.js";

const backpackList = backpackObjectArray.map((backpack) => {
  let backpackArticle = document.createElement("article");
  backpackArticle.classList.add("backpack");
  backpackArticle.setAttribute("id", backpack.id);

  backpackArticle.innerHTML = `
    <figure class="backpack__image">
      <img src=${backpack.image} alt="" loading="lazy" />
    </figure>
    <h1 class="backpack__name">${backpack.name}</h1>
    <ul class="backpack__features">
      <li class="feature backpack__volume">Volume:<span> ${
        backpack.volume
      }l</span></li>
      <li class="feature backpack__color">Color:<span> ${
        backpack.color
      }</span></li>
      <li class="feature backpack__age">Age:<span> ${backpack.backpackAge()} days old</span></li>
      <li class="feature backpack__pockets">Number of pockets:<span> ${
        backpack.pocketNum
      }</span></li>
      <li class="feature backpack__strap">Left strap length:<span> ${
        backpack.strapLength.left
      } inches</span></li>
      <li class="feature backpack__strap">Right strap length:<span> ${
        backpack.strapLength.right
      } inches</span></li>
      <li class="feature backpack__lid">Lid status: <span>${
        backpack.lidOpen ? "open" : "closed"
      }</span></li>
    </ul>
    <button class="lid-toggle">Open lid</button>
  `;

  const button = backpackArticle.querySelector(".lid-toggle");
  const status = backpackArticle.querySelector(".backpack__lid span");

  button.addEventListener(
    "click",
    function (e) {
      status.innerText === "open" ? status.innerText = "closed" : status.innerText = "open";
      this.innerHTML === 'Open lid' ? this.innerText = "Close lid" : this.innerText = "Open lid";
      this.lidOpen === true ? this.lidOpen = false : this.lidOpen = true;
      console.log(this.lidOpen);
    }
  )

  return backpackArticle;
});

const main = document.querySelector(".maincontent");

backpackList.forEach((backpack) => {
  main.append(backpack);
});
