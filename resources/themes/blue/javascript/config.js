export const Config = {
    get(key, type = String) {
        // eslint-disable-next-line
        const value = CONFIG['config.' + key]

        if (type === Boolean) {
            if (value === 'true' || value === '1') {
                return true
            }

            return false
        }

        try {
            return JSON.parse(value)
        } catch {
            return value
        }
    },
}
