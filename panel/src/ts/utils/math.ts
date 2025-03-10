export function clamp(value: number, min: number, max: number) {
    return Math.min(Math.max(value, min), max);
}

export function mod(x: number, y: number) {
    // Return x mod y (always rounded downwards, differs from x % y which is the remainder)
    return x - y * Math.floor(x / y);
}
