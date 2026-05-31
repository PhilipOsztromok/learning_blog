/**
 * Practice: Pass values between functions
 *
 * - Create two functions
 * - Main function creates article element with data from object
 * - Helper function creates.
 */

 const main = document.querySelector("main");

const webServer = {
    name: "Raspberry Pi 4",
    ip: "192.168.0.20",
    mac: "dc:a6:32:d9:4d:37",
};

function addServer(server) {
    const newArticle = document.createElement("article");
    newArticle.innerHTML =`
        <h1>${server.name}</h1>
        <h2>Addresses</h2>
        <ul>
            <li>IP Address: ${server.ip}</li>
            <li>MAC Address: ${server.mac}</li>
        </ul>
    ;`
    return newArticle;
};

const blueTitle = function() {
    const anElement = document.querySelector("h1");
    anElement.style.color="blue";
};

main.append(addServer(webServer));
blueTitle();

(function() {
    const server = {
        name: "Dev Machine",
        ip: "192.168.0.15",
        mac: "00:13:ef:f2:16:a3",
    };

        const newArticle = document.createElement("article");
        newArticle.innerHTML =`
            <h1>${server.name}</h1>
            <h2>Addresses</h2>
            <ul>
                <li>IP Address: ${server.ip}</li>
                <li>MAC Address: ${server.mac}</li>
            </ul>
        ;`
        main.append(newArticle);
})();

(() => {
    const server = {
        name: "Work Machine",
        ip: "192.168.0.10",
        mac: "f0:79:59:8c:cb:d9",
    };

        const newArticle = document.createElement("article");
        newArticle.innerHTML =`
            <h1>${server.name}</h1>
            <h2>Addresses</h2>
            <ul>
                <li>IP Address: ${server.ip}</li>
                <li>MAC Address: ${server.mac}</li>
            </ul>
        ;`
        main.append(newArticle);
})();