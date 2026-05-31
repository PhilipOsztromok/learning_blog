 import backpackObjectArray from "./components/data.js";

const article = document.querySelector("main");

// map() through the stuff array to make a new stuffItems array.
const packList = backpackObjectArray.map((item) => {
  const content = `
    <figure class="backpack__image">
      <img src=${item.image} alt="" loading="lazy" />
    </figure>
    <h1 class="backpack__name">${item.name}</h1>
    <ul class="backpack__features">
      <li class="feature backpack__volume">Volume:<span> ${
        item.volume
      }l</span></li>
      <li class="feature backpack__color">Color:<span> ${
        item.color
      }</span></li>
      <li class="feature backpack__age">Age:<span> ${item.backpackAge()} days old</span></li>
      <li class="feature backpack__pockets">Number of pockets:<span> ${
        item.pocketNum
      }</span></li>
      <li class="feature backpack__strap">Left strap length:<span> ${
        item.strapLength.left
      } inches</span></li>
      <li class="feature backpack__strap">Right strap length:<span> ${
        item.strapLength.right
      } inches</span></li>
      <li class="feature backpack__lid">Lid status:<span> ${
        item.lidOpen ? "open" : "closed"
      }</span></li>
    </ul>
  `;

  console.log("Item is :", item);
  console.log("Contents are: ", content);

  const backpack_div = document.createElement("div");
  backpack_div.innerHTML = content;
  return backpack_div;

});

packList.forEach((item) => {
  article.append(item)
});