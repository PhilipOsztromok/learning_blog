import Book from "./Book.js";

const cssInDepth = new Book("CSS In Depth", "Jim Smith", "10101010101", 2019, "O'Reilly", "on shelf");
const foundation = new Book("Foundation", "Isaac Asimov", "10101010101010", 1957, "Doubleday", "on shelf");
const atlas = new Book("World Atlas", "n/a", "0689213952728", 2021, "Collins", "on coffee table");
const magyars = new Book("Magical Magyars","David Bailey", "0956218891011", 2020, "Pitch", "loaned out");
const complete = new Book("The Complete Robot", "Isaac Asimov", "0414772405062", 1982, "Harper Collins", "reading");
							
var bookshelf=[];
bookshelf[0]=cssInDepth;
bookshelf[1]=foundation;
bookshelf[2]=atlas;
bookshelf[3]=magyars;
bookshelf[4]=complete;

console.log("Books are : ", bookshelf);

console.log("Checking status on ", complete);
complete.finishBook();
console.log("Checking status on ", complete);

console.log("Checking status on ", foundation);
foundation.startBook();
console.log("Checking status on ", foundation);
			
console.log("Checking status on ", magyars);
magyars.returnBook();
console.log("Checking status on ", magyars);

console.log("Checking status on ", cssInDepth);
cssInDepth.startBook();
console.log("Checking status on ", cssInDepth);

console.log("Checking status on ", atlas);
atlas.loanBook();
console.log("Checking status on ", atlas);