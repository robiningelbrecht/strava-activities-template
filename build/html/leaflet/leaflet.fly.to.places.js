L.Control.FlyToPlaces = L.Control.extend({
    options: {
        position: 'topright',
        places: {}
    },
    onAdd: function(map) {
        const container = L.DomUtil.create('ul', 'leaflet-control bg-gray-50');
        this.options.places.forEach((place) => {
            const item = L.DomUtil.create('li', 'p-1 cursor-pointer', container);
            item.innerHTML = '<img src="https://raw.githubusercontent.com/robiningelbrecht/strava-activities-template/master/files/flags/'+place.name.toLowerCase()+'.svg" width="20" title="'+place.name+'" />'
            // Prevent click events propagation to map.
            L.DomEvent.disableClickPropagation(item);
            L.DomEvent.on(item, 'click', function () {
                map.flyToBounds(place.bounds, {duration: 3});
            });
        });

        // Prevent right click event propagation to map.
        L.DomEvent.on(container, 'contextmenu', function (ev)
        {
            L.DomEvent.stopPropagation(ev);
        });

        // Prevent scroll events propagation to map when cursor on the div.
        L.DomEvent.disableScrollPropagation(container);

        return container;
    },
    onRemove: function() {

    }
});

L.control.flyToPlaces = function(opts) {
    return new L.Control.FlyToPlaces(opts);
}