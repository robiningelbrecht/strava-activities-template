const echarts = require('echarts');
const fs = require('fs');
const path = require('path');

const run = async () => {
    const directoryPath = path.join(__dirname, 'build');
    const files = fs.readdirSync(directoryPath);
    files.forEach(function (file) {
        if(file.endsWith('.json')){
            const fileNameParts = file.split('_');
            const chart = echarts.init(null, null, {
                renderer: 'svg',
                ssr: true,
                width: parseInt(fileNameParts[1]),
                height: parseInt(fileNameParts[2])
            });

            const absolutePath = directoryPath+'/'+file;
            const data = fs.readFileSync(absolutePath);
            const chartOptions = JSON.parse(data);

            const fileName = absolutePath.replace('.json', '.svg');
            try {
                chart.setOption(chartOptions);
                fs.writeFileSync(fileName, chart.renderToSVGString());
            } catch (e) {
            }
        }
    });

    process.exit(0);
}

run();