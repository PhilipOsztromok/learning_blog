class Book {
	constructor(title, author, isbn_13, pub_date, publisher, status) 
	
	{
		this.title=title;
		this.author=author;
		this.isbn_13=isbn_13;
		this.pub_date=pub_date;
		this.publisher=publisher;
		this.status=status;
	}
	
	// functions go here
	setStatus(newStatus) {
		this.status=newStatus;
	}

	loanBook() {
		this.status="loaned out";
	}
	
	returnBook() {
		this.status="on shelf";
	}

	startBook() {
		this.status="reading";
	}

	finishBook() {
		this.status="read";
	}
	
}

export default Book;