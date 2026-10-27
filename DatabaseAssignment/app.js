$(document).ready(function () {

    // Change only this line:
    var chartType = "bar";
    // Use "line" for a line chart

    $.ajax({
        url: "https://seagreen-fox-591983.hostingersite.com/Chartjs/data.php?node=node_1",
        method: "GET",
        dataType: "json",

        success: function (data) {
            console.log(data);

            var x_axis = [];
            var y_axis = [];

            for (var i = 0; i < data.length; i++) {
                x_axis.push(data[i].time_received);
                y_axis.push(Number(data[i].temperature));
            }

            var chartdata = {
                labels: x_axis,

                datasets: [{
                    label: "Temperature for Node 1",
                    data: y_axis,

                    backgroundColor: "rgba(14, 231, 35, 0.8)",
                    borderColor: "black",
                    borderWidth: 2,

                    tension: 0.3,
                    fill: false
                }]
            };

            var ctx = document.getElementById("mycanvas");

            new Chart(ctx, {
                type: chartType,
                data: chartdata,

                options: {
                    responsive: true,

                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: "Time"
                            }
                        },

                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: "Temperature (°C)"
                            }
                        }
                    }
                }
            });
        },

        error: function (xhr) {
            console.log("Chart error:", xhr.responseText);
        }
    });
});
