export function debounce(callback: (...args: any[]) => any, delay: number, leading: boolean = false) {
    let result: any;
    let timer: number | null = null;

    function wrapper(this: any, ...args: any[]) {
        // eslint-disable-next-line @typescript-eslint/no-this-alias
        const context = this;
        if (timer) {
            clearTimeout(timer);
        }
        if (leading && !timer) {
            result = callback.apply(context, args);
        }
        timer = window.setTimeout(() => {
            if (!leading) {
                result = callback.apply(context, args);
            }
            timer = null;
        }, delay);
        return result;
    }

    return wrapper;
}

export function throttle(callback: (...args: any[]) => any, delay: number) {
    let result: any;
    let previous = 0;
    let timer: number | null = null;

    function wrapper(this: any, ...args: any[]) {
        const now = Date.now();
        if (previous === 0) {
            previous = now;
        }
        const remaining = previous + delay - now;
        // eslint-disable-next-line @typescript-eslint/no-this-alias
        const context = this;
        if (remaining <= 0 || remaining > delay) {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
            previous = now;
            result = callback.apply(context, args);
        } else if (!timer) {
            timer = window.setTimeout(() => {
                previous = Date.now();
                result = callback.apply(context, args);
                timer = null;
            }, remaining);
        }
        return result;
    }

    return wrapper;
}

export function longClick(element: HTMLElement, callback: (event: MouseEvent) => void, timeout: number, interval: number) {
    let timer: number;
    function clear() {
        clearTimeout(timer);
    }
    element.addEventListener("mousedown", function (event: MouseEvent) {
        // eslint-disable-next-line @typescript-eslint/no-this-alias
        const context = this;
        if (event.button !== 0) {
            clear();
        } else {
            callback.call(context, event);
            timer = window.setTimeout(() => (timer = window.setInterval(callback.bind(context, event), interval)), timeout);
        }
    });
    element.addEventListener("mouseout", clear);
    window.addEventListener("mouseup", clear);
}
