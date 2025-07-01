import { app } from "../app";

const cache = new Map();
const pending = new Map();

export function passIcon(icon: string, callback: (iconData: string) => void) {
    if (cache.has(icon)) {
        callback(cache.get(icon));
        return;
    }

    if (pending.has(icon)) {
        pending.get(icon).push(callback);
        return;
    }

    pending.set(icon, [callback]);

    const request = new XMLHttpRequest();

    request.onload = function () {
        const data = this.status === 200 ? this.response : "";
        if (data !== "") {
            cache.set(icon, data);
        }

        for (const callback of pending.get(icon)) {
            callback(data);
        }

        pending.delete(icon);
    };

    request.open("GET", `${app.config.baseUri}assets/icons/svg/${icon}.svg`);
    request.send();
}

export function insertIcon(icon: string, element: HTMLElement, position: InsertPosition = "afterbegin") {
    passIcon(icon, (data) => element.insertAdjacentHTML(position, data));
}
