<script>
    document.addEventListener('livewire:init', () => {
        let known = null

        const read = () => {
            const counts = {}

            document.querySelectorAll('#fi-main-sidebar .fi-sidebar-item').forEach((item) => {
                const label = item.querySelector('.fi-sidebar-item-label')?.textContent.trim()

                if (label) {
                    counts[label] = {
                        count: parseInt(item.querySelector('.fi-sidebar-item-badge-ctn')?.textContent, 10) || 0,
                        url: item.querySelector('.fi-sidebar-item-btn')?.getAttribute('href'),
                    }
                }
            })

            return counts
        }

        const compare = () => {
            const current = read()

            if (known) {
                Object.entries(current).forEach(([label, { count, url }]) => {
                    const added = count - (known[label]?.count ?? 0)

                    if (added > 0) {
                        new FilamentNotification()
                            .title(`${added} new in ${label}`)
                            .info()
                            .actions(url ? [new FilamentNotificationAction('view').label('View').url(url)] : [])
                            .send()
                    }
                })
            }

            known = current
        }

        Livewire.hook('morphed', ({ el }) => {
            if (el.querySelector?.('#fi-main-sidebar')) {
                compare()
            }
        })

        compare()

        setInterval(() => {
            if (! document.hidden) {
                Livewire.dispatch('refresh-sidebar')
            }
        }, 10000)
    })
</script>
