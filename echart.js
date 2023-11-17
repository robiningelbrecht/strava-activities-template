const echarts = require('echarts');
const fs = require('fs');
const path = require('path');

const run = async () => {
    const directoryPath = path.join(__dirname, 'build/charts');
    let files = [];
    try {
        files = fs.readdirSync(directoryPath);
    } catch (err) {
        process.exit(0);
    }

    files.forEach(function (file) {
        if(file.endsWith('.json')){
            const absolutePath = directoryPath+'/'+file;
            const data = fs.readFileSync(absolutePath);
            const chartData = JSON.parse(data);

            const chart = echarts.init(null, null, {
                renderer: 'svg',
                ssr: true,
                width: chartData.width,
                height: chartData.height
            });

            const fileName = absolutePath.replace('.json', '.svg');
            try {
                chart.setOption(chartData.options);
                fs.writeFileSync(fileName, chart.renderToSVGString());
            } catch (e) {
            }
        }
    });

    process.exit(0);
}

run();