function toggleDetails() {

    const detail = document.querySelectorAll('details')

    if (document.getElementById('toggle-check').checked) { 
        detail.forEach(e => {
            e.removeAttribute("name")
            e.open = true
        })
    } else {
        detail.forEach(e => {
            e.setAttribute("name", "only")
            e.open = false
        })
    }
}
