export function allColumnsVisibleMap(keys, visible = true) {
    const map = {};
    keys.forEach((key) => {
        map[key] = visible;
    });
    return map;
}
